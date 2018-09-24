<?php

namespace Laybuy\Request;

/**
 * Class AbstractRequest
 * @package Laybuy\Request
 */
abstract class AbstractRequest
{
    /**
     * @var array
     */
    protected $queryParams = [];

    /**
     * @var array
     */
    protected $postParams = [];

    /**
     * @var array
     */
    protected $headerParams = [];

    /**
     * @param array $queryParams
     */
    public function setQueryParams($queryParams)
    {
        $this->queryParams = $queryParams;
    }

    /**
     * @param array $postParams
     */
    public function setPostParams($postParams)
    {
        $this->postParams = $postParams;
    }

    /**
     * @param array $headerParams
     */
    public function setHeaderParams($headerParams)
    {
        $this->headerParams = $headerParams;
    }

    /**
     * @return array
     */
    public function getQueryParams()
    {
        return $this->queryParams;
    }

    /**
     * @return array
     */
    public function getPostParams()
    {
        return $this->postParams;
    }

    /**
     * @return array
     */
    public function getHeaderParams()
    {
        return $this->headerParams;
    }

    /**
     * @return null
     */
    public function getFallbackValue()
    {
        return null;
    }

    /**
     * @return mixed
     */
    abstract public function getMethod();

    /**
     * @return mixed
     */
    abstract public function getEndpoint();

    /**
     * @return mixed
     */
    abstract public function getResponseClass();
}