<?php

use Laybuy\Response\CreateOrderResponse;

/**
 * Class LaybuyOrder
 *
 * No namespace here to be sure that that will work with PS ObjectModel
 */
class LaybuyOrder extends ObjectModel
{
    public $id_cart;

    /**
     * @var
     */
    public $token;

    /**
     * @var
     */
    public $laybuy_order_id;

    /**
     * @var
     */
    public $current_state;

    /**
     * @var
     */
    public $date_add;

    /**
     * @var
     */
    public $date_upd;

    /**
     * State constants
     */
    const STATE_UNCONFIRMED = 'unconfirmed';
    const STATE_CONFIRMED = 'confirmed';
    const STATE_CANCELED = 'canceled';
    const STATE_REFUNDED = 'refunded';
    const STATE_ERROR = 'error';

    /**
     * @var array
     */
    public static $definition = array(
        'table' => 'laybuy_orders',
        'primary' => 'id',
        'multilang' => false,
        'multilang_shop' => false,
        'fields' => [
            'id_cart' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
            'token' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 128),
            'laybuy_order_id' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'current_state' => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'date_add' => array('type' => self::TYPE_DATE, 'shop' => true, 'validate' => 'isDate'),
            'date_upd' => array('type' => self::TYPE_DATE, 'shop' => true, 'validate' => 'isDate'),
        ]
    );

    /**
     * @return bool
     */
    public function isComplete()
    {
        return 0 !== (int)$this->laybuy_order_id;
    }

    /**
     * @param $cartId
     *
     * @return LaybuyOrder|null
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function findByCartId($cartId)
    {
        $id = (int)Db::getInstance()->getValue('
          SELECT `id` 
          FROM `'._DB_PREFIX_.'laybuy_orders` 
          WHERE `id_cart` = '.(int)$cartId
        );

        if (!$id) {
            return null;
        }

        return new self($id);
    }

    /**
     * @param                     $cartId
     * @param CreateOrderResponse $response
     *
     * @return bool|LaybuyOrder|null
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function saveLaybuyOrder($cartId, CreateOrderResponse $response)
    {
        // New one
        if (null === $laybuyOrder = self::findByCartId($cartId)) {
            $laybuyOrder = new self();
            $laybuyOrder->id_cart = $cartId;
        }

        // Update or save with common data
        $laybuyOrder->token = $response->getToken();

        if (false === $laybuyOrder->save(false, true)) {
            return false;
        }

        return $laybuyOrder;
    }
}