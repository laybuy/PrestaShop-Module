<?php

use Laybuy\Exception\LaybuyException;
use Laybuy\Bridge\Prestashop\PrestashopBridge;

/**
 * Class LaybuyPaymentModuleFrontController
 */
class LaybuyPaymentModuleFrontController extends ModuleFrontController
{
    /**
     * @var bool
     */
    public $ssl = true;

    /**
     * Post process
     */
    public function postProcess()
    {
        parent::postProcess();

        try {
            // Start process
            // Redirect in the end if all has been done well
            $this->processLaybuyOrder();

        } catch (LaybuyException $e) {
            // Generate Laybuy error
            $this->errors[] = $e->getMessage();

        } catch (\Exception $e) {
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
    private function processLaybuyOrder()
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

        // Load order data from cart
        $orderData = PrestashopBridge::getCreateOrderData($cart);
        if (null === $orderData) {
            throw new LaybuyException($this->context->getTranslator()->trans(
                'Unable to create order data.',
                [],
                'Module.Laybuy.Errors'
            ));
        }

        // Request create order
        $result = $manager->createOrder(PrestashopBridge::getCreateOrderData($cart));

        if ($result->hasError()) {
            throw new LaybuyException($result->getError());
        }

        // Save
        if (false === $laybuyOrder = LaybuyOrder::saveLaybuyOrder($cart->id, $result)) {
            throw new LaybuyException($this->context->getTranslator()->trans(
                'An error occured while creating your order with Laybuy.',
                [],
                'Module.Laybuy.Errors'
            ));
        }

        // Existing
        if ($laybuyOrder->isComplete()) {
            throw new LaybuyException($this->context->getTranslator()->trans(
                'An LayBuy order has already been proceed for your cart.',
                [],
                'Module.Laybuy.Errors'
            ));
        }

        $paymentUrl = $result->getPaymentUrl();

        // Shouldn't happen
        if (empty($paymentUrl)) {
            throw new \Exception();
        }

        Tools::redirectLink($paymentUrl);
    }
}
