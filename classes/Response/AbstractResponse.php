<?php

namespace Laybuy\Response;

/**
 * Class AbstractResponse
 * @package Laybuy\Response
 */
class AbstractResponse
{
    /**
     * Associative array for storing property values
     *
     * @var mixed[]
     */
    protected $container = [];

    /**
     * Model default definition
     *
     * @var array
     */
    protected static $defaultAttributes = [
        'result' => 'string',
        'error' => 'string',
    ];

    /**
     * Model definition
     *
     * @var array
     */
    protected static $attributes = [];

    /**
     * @return array
     */
    public static function getObjectAttributes()
    {
        return array_merge(
            static::$defaultAttributes,
            static::$attributes
        );
    }

    /**
     * @param $key
     * @param $value
     */
    public function __set($key, $value)
    {
        if (!isset(self::getObjectAttributes()[$key])) {
            return;
        }

        if (property_exists($this, $key)) {
            $this->{$key} = $value;
        }

        $this->container[$key] = $value;
    }

    /**
     * @param $key
     *
     * @return mixed|null
     */
    public function __get($key)
    {
        if (!isset(self::getObjectAttributes()[$key])
            || !isset($this->container[$key])) {

            return null;
        }

        return $this->container[$key];
    }

    /**
     * Returns true if offset exists. False otherwise.
     * @param  integer $offset Offset
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return isset($this->container[$offset]);
    }

    /**
     * Gets offset.
     * @param  integer $offset Offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return isset($this->container[$offset]) ? $this->container[$offset] : null;
    }

    /**
     * Sets value based on offset.
     * @param  integer $offset Offset
     * @param  mixed   $value  Value to be set
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
        }
    }

    /**
     * Unsets offset.
     * @param  integer $offset Offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->container[$offset]);
    }

    /**
     * @return string
     */
    public function jsonSerialize()
    {
        return json_encode($this->container);
    }

    /**
     * @return mixed[]
     */
    public function toArray()
    {
        return $this->container;
    }

    /**
     * @return bool
     */
    public function hasError()
    {
        return 'SUCCESS' !== $this->container['result'];
    }

    /**
     * @return mixed|null
     */
    public function getError()
    {
        if (false === $this->hasError()) {
            return null;
        }

        return $this->container['error'];
    }
}