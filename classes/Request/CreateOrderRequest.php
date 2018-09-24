<?php

namespace Laybuy\Request;

use Laybuy\Response\CreateOrderResponse;

/**
 * Class CreateOrderRequest
 * @package Laybuy\Request
 */
class CreateOrderRequest extends AbstractRequest
{
    /**
     * CreateOrderRequest constructor.
     *
     * @param array $orderData
     */
    public function __construct(array $orderData)
    {
        $this->postParams = $orderData;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return 'POST';
    }

    /**
     * @return string
     */
    public function getEndpoint()
    {
        return '/order/create';
    }

    /**
     * @return string
     */
    public function getResponseClass()
    {
        return CreateOrderResponse::class;
    }
}