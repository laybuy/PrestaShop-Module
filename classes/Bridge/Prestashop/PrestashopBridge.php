<?php

namespace Laybuy\Bridge\Prestashop;

use Cart;
use Customer;
use Address;
use Validate;
use Currency;
use Country;
use LaybuyOrder;

/**
 * Class PrestashopBridge
 * @package Laybuy\Bridge\Prestashop
 */
class PrestashopBridge
{
    /**
     * @param Cart $cart
     *
     * @return array|null
     */
    public static function getCreateOrderData(Cart $cart)
    {
        // Get cart data
        $orderTotal = $cart->getOrderTotal();
        $products = $cart->getProducts();

        // Check cart id, amount and products
        if (!$cart->id
            || $orderTotal <= 0
            || !count($products)) {

            return null;
        }
        
        try {
            // Customer and address
            $customer = new Customer($cart->id_customer);
            $currency = new Currency($cart->id_currency);
            $addressInvoice = new Address($cart->id_address_invoice);
            $addressDelivery = new Address($cart->id_address_delivery);

            if (!Validate::isLoadedObject($customer)
                || !Validate::isLoadedObject($currency)
                || !Validate::isLoadedObject($addressInvoice)) {

                return null;
            }

            // Countries
            $countryInvoice = new Country($addressInvoice->id_country, $cart->id_lang);
            $countryDelivery = new Country($addressInvoice->id_country, $cart->id_lang);

            // Build items
            $items = [];

            foreach ($products as $product) {

                // Build product name with attributes if present
                $productName = $product['name'].
                    (!empty($product['attributes_small']) ? ': '.$product['attributes_small'] : '');

                $items[] = [
                    'id' => $product['id_product'],
                    'description' => self::_format($productName, 150),
                    'quantity' => (int)$product['cart_quantity'],
                    'price' => $product['price_wt']
                ];
            }

            // Add shipping item
            if ($shippingAmount = $cart->getOrderTotal(true, Cart::ONLY_SHIPPING)) {
                $items[] = [
                    'id' => 'SHIPPING',
                    'description' => 'Shipping',
                    'quantity' => 1,
                    'price' => $shippingAmount
                ];
            }

            return [
                'amount' => $orderTotal,
                'currency' => $currency->iso_code,
                'returnUrl' => \Context::getContext()->link->getModuleLink('laybuy', 'validation', [
                    'id' => $cart->id,
                    'key' => $cart->secure_key
                ]),
                'merchantReference' => self::getMerchantReference((string)$cart->id),
                'customer' => [
                    'firstName' => self::_format($customer->firstname, 100),
                    'lastName' => self::_format($customer->lastname, 100),
                    'email' => self::_format($customer->email, 150),
                    'phone' => self::_format(empty($addressInvoice->phone) ? $addressInvoice->phone_mobile : $addressInvoice->phone, 20)
                ],
                'billingAddress' =>  [
                    'name' => self::_format(trim($addressInvoice->firstname.' '.$addressInvoice->lastname), 200),
                    'address1' => self::_format($addressInvoice->address1, 150),
                    'address2' => self::_format($addressInvoice->address2, 150),
                    'city' => self::_format($addressInvoice->city, 100),
                    'postcode' => self::_format($addressInvoice->postcode, 20),
                    'country' => self::_format($countryInvoice->name, 100)
                ],
                'shippingAddress' =>  [
                    'name' => self::_format(trim($addressDelivery->firstname.' '.$addressDelivery->lastname), 200),
                    'address1' => self::_format($addressDelivery->address1, 150),
                    'address2' => self::_format($addressDelivery->address2, 150),
                    'city' => self::_format($addressDelivery->city, 100),
                    'postcode' => self::_format($addressDelivery->postcode, 20),
                    'country' => self::_format($countryDelivery->name, 100),
                    'phone' => self::_format(empty($addressDelivery->phone) ? $addressDelivery->phone_mobile : $addressDelivery->phone, 20)
                ],
                'items' => $items
            ];

        } catch (\Exception $e) {
            // Unable to init order data
            return null;
        }
    }

    /**
     * @param $cartId
     *
     * @return LaybuyOrder
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public static function getLaybuyOrder($cartId)
    {
        return new LaybuyOrder((int)$cartId);
    }

    /**
     * @param      $string
     * @param null $length
     * @param null $callback
     *
     * @return bool|mixed|string
     */
    private static function _format($string, $length = null, $callback = null)
    {
        if (null !== $length) {
            $string = substr($string, 0, $length);
        }

        if (null !== $callback && is_callable($callback)) {
            $string = call_user_func($callback, $string);
        }

        return $string;
    }

    /**
     * @param $cartId
     *
     * @return string
     */
    private static function getMerchantReference($cartId)
    {
        return sprintf(
            '%s-%s',
            \Tools::link_rewrite(\Context::getContext()->shop->name),
            $cartId
        );
    }
}