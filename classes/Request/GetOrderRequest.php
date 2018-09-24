<?php

namespace Laybuy\Request;

use Laybuy\Response\GetOrderResponse;

/**
 * Class GetOrderRequest
 * @package Laybuy\Request
 */
class GetOrderRequest extends AbstractRequest
{
    /**
     * @var string
     */
    private $merchantReference;
    /**
     * @var int
     */
    private $orderId;

    /**
     * GetOrderRequest constructor.
     *
     * @param null $merchantReference
     * @param null $orderId
     */
    public function __construct($merchantReference = null, $orderId = null)
    {
        $this->merchantReference = $merchantReference;
        $this->orderId = (int)$orderId;
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
        if ($this->merchantReference) {
            return '/order/merchant/'.$this->merchantReference;
        }

        return '/order/'.$this->orderId;
    }

    /**
     * @return string
     */
    public function getResponseClass()
    {
        return GetOrderResponse::class;
    }
}