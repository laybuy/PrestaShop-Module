<?php

namespace Laybuy\Request;

use Laybuy\Response\ConfirmOrderResponse;

/**
 * Class ConfirmOrderRequest
 * @package Laybuy\Request
 */
class ConfirmOrderRequest extends AbstractRequest
{
    /**
     * ConfirmOrderRequest constructor.
     *
     * @param string $token
     */
    public function __construct($token)
    {
        $this->postParams = [
            'token' => $token
        ];
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
        return '/order/confirm';
    }

    /**
     * @return string
     */
    public function getResponseClass()
    {
        return ConfirmOrderResponse::class;
    }
}