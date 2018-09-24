<?php

namespace Laybuy;

use Laybuy\Exception\LaybuyApiException;
use Laybuy\Request\AbstractRequest;
use Laybuy\Request\CancelOrderRequest;
use Laybuy\Request\ConfirmOrderRequest;
use Laybuy\Request\CreateOrderRequest;
use Laybuy\Request\GetOrderRequest;
use Laybuy\Request\StateRequest;
use Laybuy\Response\AbstractResponse;
use Laybuy\Response\ExceptionResponse;

/**
 * Class Manager
 * @package Laybuy
 */
class Manager
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var bool
     */
    private $state;

    /**
     * Manager constructor.
     *
     * @param Configuration $configuration
     */
    public function __construct(Configuration $configuration)
    {
        $this->state = $configuration->getState();
        $this->client = new Client($configuration);
    }

    /**
     * @return bool
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param Configuration $configuration
     */
    public function updateConfiguration(Configuration $configuration)
    {
        $this->client->setConfiguration($configuration);
    }

    /**
     * @return bool|null
     */
    public function checkAPI()
    {
        if (!$this->client->isReady()) {
            return null;
        }

        $result = $this->_sendRequest(new StateRequest());

        // API seams to be down only when getting an API Exception
        $this->state = $result instanceof ExceptionResponse ? false : true;

        return $this->state;
    }


    /**
     * @param array $orderData
     *
     * @return AbstractResponse
     */
    public function createOrder($orderData)
    {
        if (!is_array($orderData)) {
            return ExceptionResponse::createResponse();
        }

        $request = new CreateOrderRequest($orderData);

        return $this->_sendRequest($request);
    }

    /**
     * @param string $token
     *
     * @return AbstractResponse
     */
    public function confirmOrder($token)
    {
        if (empty($token)) {
            return ExceptionResponse::createResponse();
        }

        $request = new ConfirmOrderRequest($token);

        return $this->_sendRequest($request);
    }

    /**
     * @param null $merchantReference
     * @param null $orderId
     *
     * @return AbstractResponse
     */
    public function getOrder($merchantReference = null, $orderId = null)
    {
        if (empty($merchantReference) && empty($orderId)) {
            return ExceptionResponse::createResponse();
        }

        $request = new GetOrderRequest($merchantReference, $orderId);

        return $this->_sendRequest($request);
    }

    /**
     * @param string $token
     *
     * @return AbstractResponse
     */
    public function cancelOrder($token)
    {
        if (empty($token)) {
            return ExceptionResponse::createResponse();
        }

        $request = new CancelOrderRequest($token);

        return $this->_sendRequest($request);
    }

    /**
     * @param AbstractRequest $request
     *
     * @return AbstractResponse
     */
    private function _sendRequest(AbstractRequest $request)
    {
        try {

            return $this->client->request($request);

        } catch (LaybuyApiException $e) {
            // @nqtodo Log

            return ExceptionResponse::createResponse($e->getMessage());
        }
    }
}