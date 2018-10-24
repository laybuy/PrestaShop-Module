<?php

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;
use Laybuy\Manager;
use Laybuy\Configuration as LaybuyConfiguration;

if (!defined('_PS_VERSION_')) {
    exit;
}

// Require only entities because they don't have a namespace
require_once __DIR__.'/classes/Bridge/Prestashop/Entity/LaybuyOrder.php';

/**
 * Class Laybuy
 */
class Laybuy extends PaymentModule
{
    /**
     * @var bool
     */
    private static $_booted = false;

    /**
     * [BO]
     * @var string HTML return
     */
    private $_html = '';

    /**
     * [BO]
     * @var array errors of module configurations
     */
    private $_postErrors = array();

    /**
     * @var Manager
     */
    private $_manager;

    /**
     * @var array config keys
     */
    private $_configKeys = array(
        'LAYBUY_STATE',
        'LAYBUY_LAST_CHECK',
        'LAYBUY_MODE',
        'LAYBUY_ID',
        'LAYBUY_KEY',
    );

    /**
     * Laybuy constructor.
     */
    public function __construct()
    {
        $this->name = 'laybuy';
        $this->tab = 'payments_gateways';
        $this->version = '1.1.0';
        $this->author = 'Laybuy';
        $this->controllers = array('payment', 'validation');

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        $this->bootstrap = true;

        parent::__construct();

        // Autoload module classes
        $this->autoload();

        $this->displayName = $this->trans(
            'Laybuy payment',
            array(),
            'Modules.Laybuy.Admin'
        );
        $this->description = $this->trans(
            'Offer customers a seamless instalment payments option to spread the cost over 6 weeks, interest free.',
            array(),
            'Modules.Modules.Admin'
        );

        $this->ps_versions_compliancy = array(
            'min' => '1.7.0.0',
            'max' => _PS_VERSION_
        );

        if (!count(Currency::checkPaymentCurrencies($this->id))) {
            $this->warning = $this->trans('No currency has been set for this module.', array(), 'Modules.Laybuy.Admin');
        }

        // Init configuration and manager
        $configuration = $this->_createConfigurationFromPrestashop();
        $configuration->setDebug(false);

        $this->_manager = new Manager($configuration);
    }

    /**
     * [BO]
     * Autoloading module classes
     */
    private function autoload ()
    {
        if (self::$_booted) {
            return;
        }

        self::$_booted = true;

        spl_autoload_register(function ($class) {

            $prefix = 'Laybuy\\';
            $base_dir = realpath(dirname(__FILE__)) . '/classes/';

            $len = strlen($prefix);

            if (strncmp($prefix, $class, $len) !== 0) {
                return;
            }

            $relative_class = substr($class, $len);
            $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

            // if the file exists, require it
            if (!\Tools::file_exists_cache($file)) {
                return;
            }

            require_once $file;
        });
    }

    /**
     * @return Manager
     */
    public function getManager()
    {
        return $this->_manager;
    }

    /**
     * [BO]
     *
     * @return bool
     */
    public function install()
    {
        $result = parent::install()
            && $this->registerHook('paymentOptions')
            && $this->registerHook('displayProductPriceBlock')
            && $this->registerHook('displayAdminOrderLeft');

        $query = '
            CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'laybuy_orders` (
              `id` int(11) NOT NULL,
              `id_cart` int(11) NOT NULL,
              `token` varchar(128) NOT NULL,
              `laybuy_order_id` int(11) DEFAULT NULL,
              `current_state` enum(
                \''.LaybuyOrder::STATE_UNCONFIRMED.'\',
                \''.LaybuyOrder::STATE_CONFIRMED.'\',
                \''.LaybuyOrder::STATE_CANCELED.'\',
                \''.LaybuyOrder::STATE_ERROR.'\',
                \''.LaybuyOrder::STATE_REFUNDED.'\'
              ) NOT NULL DEFAULT \''.LaybuyOrder::STATE_UNCONFIRMED.'\',
              `date_add` datetime NOT NULL,
              `date_upd` datetime NOT NULL
            ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;
        ';

        $result &= Db::getInstance()->execute($query);

        return $result;
    }

    /**
     * [BO]
     *
     * @return bool
     */
    public function uninstall()
    {
        $result = true;

        foreach ($this->_configKeys as $configKey) {
            $result &= Configuration::deleteByName($configKey);
        }

        $result &= Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'laybuy_orders`');

        return $result
            && parent::uninstall();
    }

    /**
     * @return LaybuyConfiguration
     */
    private function _createConfigurationFromPrestashop()
    {
        try {
            return LaybuyConfiguration::createFromPrestashopConfiguration(
                \Configuration::getMultiple($this->_configKeys)
            );

        } catch (\Exception $e) {
            // Return an empty configuration
            return new LaybuyConfiguration();
        }
    }

    /**
     * [BO]
     * Module configuration
     *
     * @return string
     */
    public function getContent()
    {
        $this->_html = '';

        if (Tools::isSubmit('laybuySubmit')) {
            // Validation
            $this->_postValidation();

            if (!count($this->_postErrors)) {
                $this->_postProcess();

            } else {
                foreach ($this->_postErrors as $err) {
                    $this->_html .= $this->displayError($err);
                }
            }
        }

        // API Check in module configuration
        $apiCheckResult = $this->_checkAPI();

        // API result display
        if (null === $apiCheckResult) {
            $this->_html .= $this->displayWarning($this->trans('Please configure your Laybuy details', array(), 'Modules.Laybuy.Admin'));

        } elseif (1 === $apiCheckResult) {
            $this->_html .= $this->displayConfirmation($this->trans('Your Laybuy details are correct, API can be reached', array(), 'Modules.Laybuy.Admin'));

        } else {
            $this->_html .= $this->displayError($this->trans('Your Laybuy details may are wrong, API can\'t be reached', array(), 'Modules.Laybuy.Admin'));
        }

        // Authorized in front
        if (false === $this->isAuthorizedDefault()) {
            $this->_html .= $this->displayWarning(
                $this->trans('
                    This payment method will not be displayed, reasons can be one of the following:<br>
                    <ul>
                        <li>Your module is not active</li>
                        <li>This module is not considered as a payment module</li>
                        <li>The default currency is not actived for this payment method</li>
                        <li>The Laybuy API is not active (please check your details below)</li>
                    </ul>
                ',
                array(),
        'Modules.Laybuy.Admin'
            ));
        }

        return $this->_html.$this->renderForm();
    }

    /**
     * [BO|FO]
     * Check API and return state, manage last check date
     *
     * @param bool $refresh
     *
     * @return bool|null|string
     */
    private function _checkAPI($refresh = false)
    {
        $lastCheck = Configuration::get('LAYBUY_LAST_CHECK');
        $lastCheck = (int)@strtotime($lastCheck);

        // Set a -1 default value for inexistant state
        $currentState = (int)Configuration::get(
            'LAYBUY_STATE',
            null,
            null,
            null,
            -1
        );

        if (-1 === $currentState
            || true === $refresh
            || $lastCheck + 3600 < time()) {

            $state = (int)$this->_manager->checkAPI();

            Configuration::updateValue('LAYBUY_STATE', $state);
            Configuration::updateValue('LAYBUY_LAST_CHECK', date(\DateTime::ISO8601));

            return $state;
        }

        return $currentState;
    }

    /**
     * [BO]
     * Process to save configuration
     */
    private function _postProcess()
    {
        // Configuration process
        foreach ($this->_configKeys as $configKey) {
            if (!\Tools::isSubmit($configKey)) {
                continue;
            }
            Configuration::updateValue($configKey, Tools::getValue($configKey));
        }

        $this->_html .= $this->displayConfirmation($this->trans('Laybuy settings updated', array(), 'Admin.Notifications.Success'));

        // Update API Manager configuration
        $this->_manager->updateConfiguration($this->_createConfigurationFromPrestashop());

        // API Check in module configuration
        $this->_checkAPI(true);
    }

    /**
     * [BO]
     * Validation process of submitted configuration
     */
    private function _postValidation()
    {
        if (Tools::isSubmit('laybuySubmit')) {
            if (!Tools::getValue('LAYBUY_ID')) {
                $this->_postErrors[] = $this->trans('The "ID" field is required.', array(), 'Modules.Laybuy.Admin');

            } elseif (!Tools::getValue('LAYBUY_KEY')) {
                $this->_postErrors[] = $this->trans('The "Key" field is required.', array(), 'Modules.Laybuy.Admin');
            }
        }
    }

    /**
     * [BO]
     * Backend configuration form
     *
     * @return string
     */
    public function renderForm()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->trans('Laybuy API details', array(), 'Modules.Laybuy.Admin'),
                    'icon' => 'icon-cog'
                ),
                'input' => array(
                    array(
                        'type' => 'select',
                        'label' => $this->trans('Mode', array(), 'Modules.Laybuy.Admin'),
                        'name' => 'LAYBUY_MODE',
                        'required' => true,
                        'options' => array(
                            'query' => array(
                                array('id' => 0, 'name' => $this->trans('Sandbox', array(), 'Modules.Laybuy.Admin')),
                                array('id' => 1, 'name' => $this->trans('Live', array(), 'Modules.Laybuy.Admin')),
                            ),
                            'id' => 'id',
                            'name' => 'name',
                        )
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->trans('ID', array(), 'Modules.Laybuy.Admin'),
                        'name' => 'LAYBUY_ID',
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->trans('Key', array(), 'Modules.Laybuy.Admin'),
                        'name' => 'LAYBUY_KEY',
                        'required' => true
                    ),
                ),
                'submit' => array(
                    'title' => $this->trans('Save and Check API', array(), 'Admin.Actions'),
                )
            ),
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->id = (int)Tools::getValue('id_carrier');
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'laybuySubmit';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->_getConfigFieldsValues(),
        );

        $this->fields_form = array();

        return $helper->generateForm(array($fields_form));
    }

    /**
     * [BO]
     * Get configuration values for form
     *
     * @return array
     */
    private function _getConfigFieldsValues()
    {
        $values = array();
        foreach ($this->_configKeys as $configKey) {
            $values[$configKey] = Tools::getValue($configKey, Configuration::get($configKey));
        }

        return $values;
    }


    /**
     * [BO]
     * Hook in an order view
     *
     * @param $params
     *
     * @return bool|string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function hookDisplayAdminOrderLeft($params)
    {
        if (!isset($params['id_order'])) {
            return false;
        }

        $order = new Order((int)$params['id_order']);

        if (!Validate::isLoadedObject($order)) {
            return false;
        }

        if ($order->module !== $this->name) {
            return false;
        }

        $laybuyOrderCurrentState = null;
        $laybuyOrder = LaybuyOrder::findByCartId($order->id_cart);

        if (null !== $laybuyOrder) {
            $laybuyOrderCurrentState = $laybuyOrder->current_state;
        }

        // Get Laybuy order
        $orderApi = $this->_manager->getOrder($order->id_cart);

        // Process
        $this->_processAdmin($laybuyOrder, $orderApi);

        $this->smarty->assign([
            'laybuyOrderCurrentState' => $laybuyOrderCurrentState,
            'laybuyOrder' => $laybuyOrder,
            'laybuyOrderApi' => $orderApi
        ]);

        return $this->display(__DIR__, 'views/templates/hook/admin.tpl');
    }

    /**
     * [BO]
     * Processes in the order's view
     *
     * @param $laybuyOrder
     * @param $orderApi
     */
    private function _processAdmin($laybuyOrder, $orderApi)
    {
        if (null === $laybuyOrder
            || null === $orderApi) {
            return;
        }
    }

    /**
     * [FO]
     *
     * @param $params
     *
     * @return array|bool
     */
    public function hookPaymentOptions($params)
    {
        if (!$this->isAuthorizedCart($params['cart'])) {
            return false;
        }

        $orderTotal = $params['cart']->getOrderTotal();
        $orderTotal = ceil($orderTotal / 6);

        $priceFormatter = new \PrestaShop\PrestaShop\Adapter\Product\PriceFormatter();
        $orderTotalFormatted = $priceFormatter->format($orderTotal);

        $this->smarty->assign([
            'laybuyAmountByWeek' => $orderTotalFormatted
        ]);

        $newOption = new PaymentOption();
        $newOption->setCallToActionText($this->trans('Pay with Laybuy', array(), 'Modules.Laybuy.Admin'))
            ->setAction($this->context->link->getModuleLink($this->name, 'payment', [], true))
            ->setAdditionalInformation($this->fetch('module:laybuy/views/templates/hook/infos.tpl'));

        return [$newOption];
    }

    /**
     * [FO]
     *
     * @param $params
     *
     * @return array|bool
     */
    public function hookDisplayProductPriceBlock($params)
    {
        // Check hook type
        if (!isset($params['type'])
            || 'after_price' !== $params['type']) {
            return false;
        }

        // Check cart
        if (isset($params['cart'])
            && Validate::isLoadedObject($params['cart'])
            && !$this->isAuthorizedCart($params['cart'])) {
            return false;
        }

        // Check product
        if (!isset($params['product'])
            || !isset($params['product']['price_amount'])
            || 0 === $params['product']['price_amount']) {
            return false;
        }

        // Divide price and format it
        $productPriceDivided = ceil($params['product']['price_amount'] / 6);

        $priceFormatter = new \PrestaShop\PrestaShop\Adapter\Product\PriceFormatter();
        $productPriceDividedFormatted = $priceFormatter->format($productPriceDivided);


        $this->context->smarty->assign([
            'laybuyProductAmountByWeek' => $productPriceDividedFormatted
        ]);

        return $this->display(__FILE__,'views/templates/hook/product.tpl');
    }

    /**
     * [FO]
     * Check if module is authorized
     *
     * @param $cart
     *
     * @return bool
     */
    public function isAuthorizedDefault()
    {
        return $this->isAuthorized((int)Configuration::get('PS_CURRENCY_DEFAULT'));
    }

    /**
     * [FO]
     * Check if module is authorized
     *
     * @param $cart
     *
     * @return bool
     */
    public function isAuthorizedCart($cart)
    {
        return $this->isAuthorized($cart->id_currency);
    }

    /**
     * [FO]
     * Check if module is authorized
     *
     * @param $cart
     *
     * @return bool
     */
    public function isAuthorized($currencyId = null)
    {
        // Module state
        if (!$this->active) {
            return false;
        }

        // API Check
        if (1 !== $this->_checkAPI()) {
            return false;
        }

        // Authorized method
        $authorized = false;
        $modules = Module::getPaymentModules();
        foreach ($modules as $module) {
            if ($this->name === $module['name']) {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            return $authorized;
        }

        if (null === $currencyId) {
            return true;
        }

        // Setted currency
        $currency_order = new Currency((int)$currencyId);
        $currencies_module = $this->getCurrency((int)$currencyId);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }

        return false;
    }
}