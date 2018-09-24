<?php

use Laybuy\Exception\LaybuyException;

/**
 * Class LaybuyValidationModuleFrontController
 */
class LaybuyValidationModuleFrontController extends ModuleFrontController
{
    /**
     * Post process
     */
    public function postProcess()
    {
        parent::postProcess();

        $cart = $this->context->cart;
        $customer = new Customer($cart->id_customer);
        $key = trim(Tools::getValue('key'));
        $cartId = (int)Tools::getValue('id');
        $status = trim(Tools::getValue('status'));

        try {
            // Status
            if ('SUCCESS' !== $status) {
                throw new \Exception('Laybuy return in error');
            }

            // Check with context
            if (false === Validate::isLoadedObject($customer)
                || $cart->id_customer == 0
                || $cart->id_address_delivery == 0
                || $cart->id_address_delivery == 0
                || $cart->id_address_invoice == 0
                || $cart->id != $cartId
                || $cart->secure_key != $key) {

                throw new \Exception('Unable to check customer and cart data.');
            }

            // Check if an order exists
            if ($cart->orderExists()) {
                \Tools::redirect('index');
            }

            // Start process
            // Redirect in the end if all has been done well
            $this->processValidation();

        } catch (LaybuyException $e) {
            // Generate Laybuy error
            $this->errors[] = $e->getMessage();

        } catch (\Exception $e) {
            // Prestashop log
            PrestaShopLogger::addLog(
                sprintf('[Laybuy] - %s', $e->getMessage()),
                2,
                $e->getCode(),
                'Cart',
                $cartId
            );

            // Generate global error
            $this->errors[] = $this->context->getTranslator()->trans(
                'An error occured with your order, please try again later or choose another payment method',
                [],
                'Module.Laybuy.Errors'
            );
        }

        $this->redirectWithNotifications(
            $this->context->link->getPageLink('order', true, null, ['step' => 3])
        );
    }

    /**
     * @throws LaybuyException
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function processValidation()
    {
        $manager = $this->module->getManager();
        $cart = $this->context->cart;

        // Is user authorized and payment method active
        if (false === $this->module->isAuthorizedCart($cart)
            || false === $manager->getState()) {

            throw new LaybuyException($this->context->getTranslator()->trans(
                'Unable to load Laybuy payment method.',
                [],
                'Module.Laybuy.Errors'
            ));
        }

        // Laybuy validation
        if (null === $laybuyOrder = LaybuyOrder::findByCartId($cart->id)) {
            throw new LaybuyException($this->context->getTranslator()->trans(
                'Your order has not been initialized properly, please try again.',
                [],
                'Module.Laybuy.Errors'
            ));
        }

        // Request confirm order
        $result = $manager->confirmOrder($laybuyOrder->token);

        // Set order id
        $laybuyOrder->laybuy_order_id = (int)$result->getOrderId();

        // API error or Declined payment
        if ($result->hasError()) {

            $laybuyOrder->current_state = LaybuyOrder::STATE_ERROR;
            $laybuyOrder->update();

            throw new LaybuyException($result->getError());
        }

        // Complete order
        $laybuyOrder->current_state = LaybuyOrder::STATE_CONFIRMED;

        // Specific error management here to cancel Laybuy order if an error occured in Prestashop validation process
        try {
            if (false === $laybuyOrder->update()) {
                throw new \Exception('Unable to update Laybuy Order');
            }

            // Prepare data to validate order
            $customer = new Customer($cart->id_customer);
            $currency = new Currency($cart->id_currency);
            $total = (float)$cart->getOrderTotal(true, Cart::BOTH);

            $this->module->validateOrder(
                (int)$cart->id,
                Configuration::get('PS_OS_PAYMENT'),
                $total,
                $this->module->displayName,
                null,
                [],
                (int)$currency->id,
                false,
                $customer->secure_key
            );

            // Clear cart context
            unset($this->context->cookie->id_cart, $this->context->cookie->checkedTOS);

            Tools::redirect('index.php?controller=order-confirmation&id_cart='.(int)$cart->id.'&id_module='.(int)$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);

        } catch (\Exception $e) {
            // Cancel laybuy order
            $manager->cancelOrder($laybuyOrder->token);

            // Update Laybuy order
            $laybuyOrder->current_state = LaybuyOrder::STATE_CANCELED;
            $laybuyOrder->update();

            // Generate global error
            $this->errors[] = $this->context->getTranslator()->trans(
                'An error occured with your order, please check your account and contact us.',
                [],
                'Module.Laybuy.Errors'
            );

            $this->redirectWithNotifications(
                $this->context->link->getPageLink('cart')
            );
        }
    }
}
