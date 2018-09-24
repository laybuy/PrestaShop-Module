<?php

namespace Laybuy\Response;

/**
 * Class CreateOrderResponse
 * @package Laybuy\Response
 */
class CreateOrderResponse extends AbstractResponse
{
    /**
     * Model definition
     *
     * @var array
     */
    protected static $attributes = [
        'token' => 'string',
        'paymentUrl' => 'string'
    ];

    /**
     * @return mixed
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @return mixed
     */
    public function getPaymentUrl()
    {
        return $this->paymentUrl;
    }
}