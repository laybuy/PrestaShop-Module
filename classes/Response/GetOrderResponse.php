<?php

namespace Laybuy\Response;

/**
 * Class GetOrderResponse
 * @package Laybuy\Response
 */
class GetOrderResponse extends AbstractResponse
{
    /**
     * Model definition
     *
     * @var array
     */
    protected static $attributes = [
        'token' => 'string',
        'orderId' => 'int',
        'amount' => 'float',
        'currency' => 'string',
        'merchantReference' => 'int',
        'processed' => '\DateTime',
    ];
}