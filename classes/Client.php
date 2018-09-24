<?php

namespace Laybuy;

use Laybuy\Exception\LaybuyApiException;
use Laybuy\Request\AbstractRequest;
use Laybuy\Response\AbstractResponse;

class Client
{
    private $configuration;

    /**
     * @var Serializer
     */
    private $serializer;

    public static $POST = 'POST';
    public static $GET = 'GET';

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
        $this->serializer = new Serializer();
    }

    /**
     * @param Configuration $configuration
     */
    public function setConfiguration($configuration)
    {
        $this->configuration = $configuration;
    }

    public function isReady()
    {
        return $this->configuration->isComplete();
    }

    /**
     * @return AbstractResponse|object|array|null
     *
     * @throws LaybuyApiException
     */
    public function request(AbstractRequest $request)
    {
        try {
            // API Call
            list($response, $statusCode, $httpHeader) = $this->_callApi(
                $request->getEndpoint(),
                $request->getMethod(),
                $request->getHeaderParams(),
                $request->getQueryParams(),
                $request->getPostParams()
            );

            // Deserialize response
            return $this->serializer->deserialize(
                $response,
                $request->getResponseClass(),
                $httpHeader
            );

        } catch (LaybuyApiException $e) {
            // Set request fallback value
            $e->setFallbackValue($request->getFallbackValue());

            throw $e;
        }
    }

    private function _callApi(
        $resourcePath,
        $method,
        $headerParams = [],
        $queryParams = [],
        $postData = [],
        $responseType = null
    )
    {
        // Basic auth
        $headerParams['Authorization'] = 'Basic '.base64_encode(
                $this->configuration->getId()
                .':'
                .$this->configuration->getKey()
            );

        // Construct the http header
        $headerParams = array_merge(
            (array)$this->configuration->getDefaultHeaders(),
            (array)$headerParams
        );

        $headers = [];
        foreach ($headerParams as $key => $val) {
            $headers[] = sprintf('%s: %s', $key, $val);
        }

        // Post data
        if ($postData
            && in_array('Content-Type: application/x-www-form-urlencoded', $headers, true)) {
            $postData = http_build_query($postData);

        } elseif ((is_object($postData) or is_array($postData))
            && !in_array('Content-Type: multipart/form-data', $headers, true)) {
            $postData = json_encode(Serializer::sanitizeForSerialization($postData));
        }

        // Build URL
        $url = $this->configuration->getHost().$resourcePath;

        // Query params
        if (!empty($queryParams)) {
            $url = ($url.'?'.http_build_query($queryParams));
        }

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        if ($method === self::$POST) {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);

        } elseif ($method !== self::$GET) {
            throw new LaybuyApiException('Method '.$method.' is not recognized.');
        }
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_USERAGENT, $this->configuration->getUserAgent());

        // Debugging for curl
        if ($this->configuration->isDebug()) {
            error_log(
                "[DEBUG] HTTP Request body  ~BEGIN~".PHP_EOL.print_r($postData, true).PHP_EOL."~END~".PHP_EOL,
                3,
                $this->configuration->getDebugFile()
            );

            curl_setopt($curl, CURLOPT_VERBOSE, 1);
        } else {
            curl_setopt($curl, CURLOPT_VERBOSE, 0);
        }

        // Obtain the HTTP response headers
        curl_setopt($curl, CURLOPT_HEADER, 1);

        // Make the request
        $response = curl_exec($curl);
        $http_header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $http_header = $this->httpParseHeaders(substr($response, 0, $http_header_size));
        $http_body = substr($response, $http_header_size);
        $response_info = curl_getinfo($curl);

        // Debug HTTP response body
        if ($this->configuration->isDebug()) {
            error_log(
                "[DEBUG] HTTP Response body ~BEGIN~".PHP_EOL.print_r($http_body, true).PHP_EOL."~END~".PHP_EOL,
                3,
                $this->configuration->getDebugFile()
            );
        }

        // Handle the response
        if (0 === $response_info['http_code']) {
            $curl_error_message = curl_error($curl);

            // curl_exec can sometimes fail but still return a blank message from curl_error().
            if (!empty($curl_error_message)) {
                $error_message = "API call to $url failed: $curl_error_message";
            } else {
                $error_message = "API call to $url failed, but for an unknown reason. ".
                    "This could happen if you are disconnected from the network.";
            }

            $exception = new LaybuyApiException($error_message, 0, null, null);
            $exception->setResponseObject($response_info);

            throw $exception;

        } elseif ($response_info['http_code'] >= 200 && $response_info['http_code'] <= 299) {
            // return raw body if response is a file
            if ($responseType === '\SplFileObject' || $responseType === 'string') {
                return [$http_body, $response_info['http_code'], $http_header];
            }

            $data = json_decode($http_body);
            if (json_last_error() > 0) { // if response is a string
                $data = $http_body;
            }

        } else {

            throw new LaybuyApiException(
                "[".$response_info['http_code']."] Error connecting to the API ($url)",
                $response_info['http_code']
            );
        }

        return [$data, $response_info['http_code'], $http_header];
    }

    /**
     * Return an array of HTTP response headers
     *
     * @param string $raw_headers A string of raw HTTP response headers
     *
     * @return string[] Array of HTTP response heaers
     */
    private function httpParseHeaders($raw_headers)
    {
        // ref/credit: http://php.net/manual/en/function.http-parse-headers.php#112986
        $headers = [];
        $key = '';

        foreach (explode("\n", $raw_headers) as $h) {
            $h = explode(':', $h, 2);

            if (isset($h[1])) {
                if (!isset($headers[$h[0]])) {
                    $headers[$h[0]] = trim($h[1]);
                } elseif (is_array($headers[$h[0]])) {
                    $headers[$h[0]] = array_merge($headers[$h[0]], [trim($h[1])]);
                } else {
                    $headers[$h[0]] = array_merge([$headers[$h[0]]], [trim($h[1])]);
                }

                $key = $h[0];
            } else {
                if (substr($h[0], 0, 1) === "\t") {
                    $headers[$key] .= "\r\n\t".trim($h[0]);
                } elseif (!$key) {
                    $headers[0] = trim($h[0]);
                }
                trim($h[0]);
            }
        }

        return $headers;
    }
}