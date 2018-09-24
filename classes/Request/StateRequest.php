<?php

namespace Laybuy\Request;

use Laybuy\Response\StateResponse;

/**
 * Class StateRequest
 * @package Laybuy\Request
 */
class StateRequest extends AbstractRequest
{
    /**
     * @return mixed|string
     */
    public function getMethod()
    {
        return 'GET';
    }

    /**
     * @return mixed|string
     */
    public function getEndpoint()
    {
        return '/order/0';
    }

    /**
     * @return mixed|string
     */
    public function getResponseClass()
    {
        return StateResponse::class;
    }
}