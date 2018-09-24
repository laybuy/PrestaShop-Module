<?php

namespace Laybuy\Response;

/**
 * Class ConfirmOrderResponse
 * @package Laybuy\Response
 */
class ConfirmOrderResponse extends AbstractResponse
{
    /**
     * Model definition
     *
     * @var array
     */
    protected static $attributes = [
        'orderId' => 'int',
    ];

    /**
     * @return mixed
     */
    public function getOrderId()
    {
        return $this->orderId;
    }
}