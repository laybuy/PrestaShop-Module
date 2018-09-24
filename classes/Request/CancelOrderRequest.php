<?php

namespace Laybuy\Request;

use Laybuy\Response\CancelOrderResponse;

/**
 * Class CancelOrderRequest
 * @package Laybuy\Request
 */
class CancelOrderRequest extends AbstractRequest
{
    /**
     * @var string $token
     */
    private $token;

    /**
     * CancelOrderRequest constructor.
     *
     * @param array $orderData
     */
    public function __construct($token)
    {
        $this->token = $token;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return 'GET';
    }

    /**
     * @return string
     */
    public function getEndpoint()
    {
        return '/order/cancel/'.$this->token;
    }

    /**
     * @return string
     */
    public function getResponseClass()
    {
        return CancelOrderResponse::class;
    }
}