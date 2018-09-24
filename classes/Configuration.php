<?php

namespace Laybuy;

/**
 * Class Configuration
 */
class Configuration
{
    /**
     * @var bool|null API state
     */
    private $state;

    /**
     * @var \DateTime API last check
     */
    private $lastCheck;

    /**
     * @var int API mode
     */
    private $mode = self::MODE_SANDBOX;

    /**
     * @var string API ID
     */
    private $id;

    /**
     * @var string API Key
     */
    private $key;

    /**
     * @var bool
     */
    private $debug = false;

    /**
     * The default header(s)
     *
     * @var array
     */
    protected $defaultHeaders = [
        'Accept' => 'application/hal+json'
    ];

    /**
     * User agent of the HTTP request, set to "Laybuy" by default
     *
     * @var string
     */
    protected $userAgent = "Laybuy/1.0.0/php";

    /**
     * Debug file location (log to STDOUT by default)
     *
     * @var string
     */
    protected $debugFile = 'php://output';

    const MODE_SANDBOX = 0;
    const MODE_LIVE = 1;

    const HOST_SANDBOX = 'https://sandbox-api.laybuy.com';
    const HOST_LIVE = 'https://api.laybuy.com';

    /**
     * @param array $configuration
     *
     * @return Configuration
     */
    public static function createFromPrestashopConfiguration(array $configuration)
    {
        $object = new Configuration();

        if (isset($configuration['LAYBUY_STATE'])) {
            $object->setState((bool)$configuration['LAYBUY_STATE']);
        }

        if (isset($configuration['LAYBUY_LAST_CHECK']) && false !== $configuration['LAYBUY_LAST_CHECK']) {
            try {
                $object->setLastCheck(new \DateTime($configuration['LAYBUY_LAST_CHECK']));

            } catch (\Exception $e) {}
        }

        if (isset($configuration['LAYBUY_DEBUG'])) {
            $object->setDebug((bool)$configuration['LAYBUY_DEBUG']);
        }

        if (isset($configuration['LAYBUY_MODE'])) {
            $object->setMode((int)$configuration['LAYBUY_MODE']);
        }

        if (isset($configuration['LAYBUY_ID']) && !empty($configuration['LAYBUY_ID'])) {
            $object->setId((string)$configuration['LAYBUY_ID']);
        }

        if (isset($configuration['LAYBUY_KEY']) && !empty($configuration['LAYBUY_KEY'])) {
            $object->setKey((string)$configuration['LAYBUY_KEY']);
        }

        return $object;
    }

    public function isComplete()
    {
        return !empty($this->id) && !empty($this->key);
    }

    /**
     * @return string
     */
    public function getHost()
    {
        if (self::MODE_LIVE === $this->mode) {
            return self::HOST_LIVE;
        }

        return self::HOST_SANDBOX;
    }

    /**
     * Gets the default header
     *
     * @return array An array of default header(s)
     */
    public function getDefaultHeaders()
    {
        return $this->defaultHeaders;
    }

    public function getUserAgent()
    {
        return $this->userAgent;
    }

    /**
     * @return bool
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param bool $state
     */
    public function setState($state)
    {
        $this->state = $state;
    }

    /**
     * @return mixed
     */
    public function getLastCheck()
    {
        return $this->lastCheck;
    }

    /**
     * @param mixed $lastCheck
     */
    public function setLastCheck($lastCheck)
    {
        $this->lastCheck = $lastCheck;
    }

    /**
     * @return int
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * @param int $mode
     */
    public function setMode($mode)
    {
        $this->mode = $mode;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param mixed $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * @return bool
     */
    public function isDebug()
    {
        return $this->debug;
    }

    /**
     * @param bool $debug
     */
    public function setDebug($debug)
    {
        $this->debug = $debug;
    }

    /**
     * @return string
     */
    public function getDebugFile()
    {
        return $this->debugFile;
    }

    /**
     * @param string $debugFile
     */
    public function setDebugFile($debugFile)
    {
        $this->debugFile = $debugFile;
    }
}