<?php
/**
 * Copyright (C) 2026-2026 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <modules@thirtybees.com>
 * @copyright 2026-2026 thirty bees
 * @license   Academic Free License (AFL 3.0)
 * @version   2.0.0
 */

use Thirtybees\Module\TbPayPal\Api\PayPalAPI;
use Thirtybees\Module\TbPayPal\Factory;
use Thirtybees\Module\TbPayPal\Settings\Settings;
use Thirtybees\Module\TbPayPal\Logger\Logger;

if (!defined('_TB_VERSION_')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

class TbPayPalCheckout extends PaymentModule
{
    const INPUT_MODE = 'input_mode';
    const INPUT_CLIENT_ID = 'input_client_id';
    const INPUT_CLIENT_SECRET = 'input_client_secret';
    const INPUT_INTENT = 'input_intent';
    const INPUT_DEBUG_MODE = 'input_debug_mode';

    /**
     * @var Factory
     */
    private Factory $factory;

    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->name = 'tbpaypalcheckout';
        $this->tab = 'payments_gateways';
        $this->version = '0.0.2';
        $this->author = 'thirty bees';
        $this->need_instance = 1;
        $this->bootstrap = true;
        $this->module_key = '';
        $this->controllers = ['checkout', 'return', 'validation', 'webhook'];

        parent::__construct();

        $this->displayName = $this->l('PayPal Checkout Pro');
        $this->description = $this->l('Accept payments via PayPal Checkout using modern REST API v2');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall PayPal Checkout Pro?');
        
        $this->ps_versions_compliancy = ['min' => '1.6', 'max' => _PS_VERSION_];

        $this->factory = new Factory($this);
    }

    /**
     * Install Module
     *
     * @return bool
     * @throws PrestaShopException
     */
    public function install($createTables = true)
    {
        $logger = $this->factory->getLogger();
        $logger->debug('Installing module');

        if (!parent::install()) {
            $logger->error('Core module installation failed');
            return false;
        }

        if (! $this->installDb($logger, $createTables)) {
            $logger->error('Database table installation failed');
            return false;
        }

        // Register hooks
        if (!$this->registerHook('payment') ||
            !$this->registerHook('paymentReturn') ||
            !$this->registerHook('displayPaymentEU') ||
            !$this->registerHook('header')
        ) {
            $logger->error('Hooks installation failed');
        }

        // Set default configuration
        $this->factory->getSettings()->reset();
        return true;
    }


    /**
     * Uninstall Module
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function uninstall($full = true)
    {
        $logger = $this->factory->getLogger();
        if ($full) {
            $logger->debug('Uninstalling module, destroying data');
        } else {
            $logger->debug('Uninstalling module, keeping data');
        }
        $this->factory->getSettings()->delete();
        $this->uninstallDb($logger, $full);
        return parent::uninstall();
    }

    /**
     * @return bool
     * @throws PrestaShopException
     */
    public function reset()
    {
        return (
            $this->uninstall(false) &&
            $this->install(false)
        );
    }

    /**
     * @param Logger $logger
     * @param bool $create
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function installDb(Logger $logger, $create)
    {
        if (!$create) {
            $logger->debug('Skipping creation of database table');
            return true;
        }
        return $this->executeSqlScript($logger, 'install');
    }

    /**
     * @param Logger $logger
     * @param bool $drop
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function uninstallDb(Logger $logger, $drop)
    {
        if (!$drop) {
            $logger->debug('Skipping dropping of database table');
            return true;
        }
        return $this->executeSqlScript($logger, 'uninstall', false);
    }

    /**
     * @param Logger $logger
     * @param $script
     * @param bool $check
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function executeSqlScript(Logger $logger, $script, $check = true)
    {
        $logger->debug('Executing sql script: ' . $script);

        $file = dirname(__FILE__) . '/sql/' . $script . '.sql';
        if (!file_exists($file)) {
            $logger->error('SQL script not found: ' . $script);
            return false;
        }
        $sql = file_get_contents($file);
        if (!$sql) {
            $logger->error('SQL script is empty: ' . $script);
            return false;
        }

        $sql = str_replace(
            [
                'PREFIX_',
                'ENGINE_TYPE',
                'CHARSET_TYPE',
                'COLLATE_TYPE',
            ],
            [
                _DB_PREFIX_,
                _MYSQL_ENGINE_,
                'utf8mb4',
                'utf8mb4_unicode_ci',
            ],
            $sql
        );
        $sql = preg_split("/;\s*[\r\n]+/", $sql);
        $conn = Db::getInstance();
        foreach ($sql as $statement) {
            $stmt = trim($statement);
            if ($stmt) {
                try {
                    $logger->debug('Executing sql statement: ' . $stmt);
                    if (! $conn->execute($stmt)) {
                        $error = $conn->getMsgError();
                        PrestaShopLogger::addLog($this->name . ": sql script $script: $stmt: " . $error);
                        $logger->error('SQL statement failed: ' . $error);
                        if ($check) {
                            return false;
                        }
                    }
                } catch (Exception $e) {
                    PrestaShopLogger::addLog($this->name . ": sql script $script: $stmt: $e");
                    if ($check) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    /**
     * Module Configuration Page
     *
     * @return string
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function getContent()
    {
        $messages = '';

        $settings = $this->factory->getSettings();
        $logger = $this->factory->getLogger();
        $api = $this->factory->getApi();

        // Handle form submission
        if (Tools::isSubmit('submit' . $this->name)) {
            $messages .= $this->processConfigurationForm($api, $settings, $logger);
        }

        // Display any warnings
        $messages .= $this->displayWarnings($settings);

        $this->context->smarty->assign([
            'tbpaypal_messages' => $messages,
            'tbpaypal_form' => $this->displayConfigurationForm($settings),
            'tbpaypal_show_manual' => !$settings->validCredentials(),
            'tbpaypal_sandbox' => $settings->getMode() === Settings::MODE_SANDBOX,
            'tbpaypal_webhook_url' => $this->getWebhookUrl(),
            'tbpaypal_shop_enabled' => (bool)Configuration::get('PS_SHOP_ENABLE'),
            'tbpaypal_multistore' => Shop::isFeatureActive(),
            'tbpaypal_payment_link' => $this->context->link->getAdminLink('AdminPayment'),
            'tbpaypal_log_file' => 'log/paypal-' . date('Ymd') . '.log',
        ]);

        return $this->display(__FILE__, 'views/templates/admin/configure.tpl');
    }

    /**
     * Display Configuration Warnings
     *
     * @return string
     */
    protected function displayWarnings(Settings $settings)
    {
        $output = '';

        // Warning if credentials are missing
        if (! $settings->validCredentials()) {
            $output .= $this->displayWarning(
                $this->l('PayPal API credentials are not configured. The module will not work until you enter your Client ID and Secret.')
            );
        }
        
        // Info message if in sandbox mode (ThirtyBees compatible)
        if ($settings->getMode() === Settings::MODE_SANDBOX) {
            $output .= '<div class="bootstrap">';
            $output .= '<div class="alert alert-info">';
            $output .= '<button type="button" class="close" data-dismiss="alert">&times;</button>';
            $output .= '<i class="icon-info-sign"></i> ';
            $output .= '<strong>' . $this->l('Sandbox Mode Active') . '</strong><br>';
            $output .= $this->l('Module is in SANDBOX mode. No real transactions will be processed. Switch to Live mode when ready for production.');
            $output .= '</div>';
            $output .= '</div>';
        }
        
        // Success message if configured for live mode
        if ($settings->validCredentials() && $settings->getMode() === Settings::MODE_LIVE) {
            $output .= '<div class="bootstrap">';
            $output .= '<div class="alert alert-success">';
            $output .= '<button type="button" class="close" data-dismiss="alert">&times;</button>';
            $output .= '<i class="icon-ok-sign"></i> ';
            $output .= '<strong>' . $this->l('Live Mode Active') . '</strong><br>';
            $output .= $this->l('Module is configured and ready to process real transactions.');
            $output .= '</div>';
            $output .= '</div>';
        }
        
        return $output;
    }

    /**
     * Process Configuration Form Submission
     *
     * @param PayPalAPI $api
     * @param Settings $settings
     * @param Logger $logger
     * @return string
     * @throws PrestaShopException
     */
    protected function processConfigurationForm(PayPalAPI $api, Settings $settings, Logger $logger): string
    {
        // Validate and save Mode
        $mode = (string)Tools::getValue(static::INPUT_MODE);
        if (! in_array($mode, [Settings::MODE_LIVE, Settings::MODE_SANDBOX])) {
            $mode = Settings::MODE_SANDBOX;
        }
        $settings->setMode($mode);

        // Validate and save Client ID
        $clientId = trim((string)Tools::getValue(static::INPUT_CLIENT_ID));
        $oldClientId = $settings->getClientId();
        $oldClientSecret = $settings->getClientSecret();
        $clientSecret = trim((string)Tools::getValue(static::INPUT_CLIENT_SECRET));

        $credentialsSet = ($clientId && $clientSecret && ($oldClientId !== $clientId || $oldClientSecret !== $clientSecret));

        $settings->setClientId($clientId);
        $settings->setClientSecret($clientSecret);
        if ($credentialsSet) {
            $settings->setWebhookId('');
        }

        // Validate and save Intent
        $intent = Tools::getValue(static::INPUT_INTENT);
        if (!in_array($intent, [Settings::INTENT_CAPTURE, Settings::INTENT_AUTHORIZE])) {
            $intent = Settings::INTENT_CAPTURE;
        }
        $settings->setIntent($intent);

        // Save Debug Mode
        $settings->setDebugMode(Tools::getBoolValue(static::INPUT_DEBUG_MODE));

        $settings->save();

        // Log successful save
        $logger->debug('Configuration saved', [
            'mode' => $mode,
            'has_client_id' => !empty($settings->getClientId()),
            'has_client_secret' => !empty($settings->getClientSecret()),
            'intent' => $intent,
            'debug_mode' => $settings->isDebugMode()
        ]);

        $response = $this->displayConfirmation($this->l('Settings saved successfully'));

        // check client id
        $error = $this->testApiCredentials($api, $logger);
        if ($error) {
            $response .= $this->displayError($error);
        } else {
            if (! $settings->getWebhookId()) {
                $webhookId = $this->resolveWebhook($api, $logger);
                if (!$webhookId) {
                    $webhookId = $this->createWebhook($api, $logger);
                    if (!$webhookId) {
                        $logger->error('Failed to register webhook record');
                    }
                }
                if ($webhookId) {
                    $settings->setWebhookId($webhookId);
                    $settings->save();
                }
            }
        }

        return $response;
    }

    /**
     * @param PayPalAPI $api
     * @param Logger $logger
     * @return string|null
     */
    protected function testApiCredentials(PayPalAPI $api, Logger $logger): ?string
    {
        $logger->debug('Testing API credentials');
        try {
            $token = $api->getAccessToken();
        } catch (Throwable $e) {
            $logger->exception('Unable to authenticate with API', $e);
            return $this->l('Unable to authenticate with PayPal API');
        }
        $logger->debug('API credentials are working properly');
        return null;
    }

    /**
     * @param PayPalAPI $api
     * @param Logger $logger
     * @return string
     */
    protected function createWebhook(PayPalAPI $api, Logger $logger): string
    {
        $logger->debug('Creating new webhook');
        try {
            return (string)$api->createWebhook($this->getWebhookUrl());
        } catch (Throwable $e) {
            $logger->exception('Failed to create webhook', $e);
            return '';
        }
    }

    /**
     * @param PayPalAPI $api
     * @param Logger $logger
     * @return string
     */
    protected function resolveWebhook(PayPalAPI $api, Logger $logger): string
    {
        $logger->debug('Resolving webhook');
        try {
            $webhookUrl = $this->getWebhookUrl();
            $webhooks = $api->getWebhooks();
            if ($webhooks) {
                $logger->debug('Found ' . count($webhooks) . ' configured webhooks');
                foreach ($webhooks as $webhook) {
                    if ($webhook['url'] === $webhookUrl) {
                        $id = $webhook['id'];
                        $logger->debug('Found webhook with id ' . $id);
                        return $id;
                    } else {
                        $logger->debug('Ignoring webhook for url "'.$webhook['url'].'"');
                    }
                }
            } else {
                $logger->debug('No webhooks found');
            }
            return '';
        } catch (Throwable $e) {
            $logger->exception("Failed to resolve webhook", $e);
            return '';
        }
    }

    /**
     * Display Configuration Form
     *
     * @param Settings $settings
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    protected function displayConfigurationForm(Settings $settings): string
    {
        // Initialize Helper Form
        $helper = new HelperForm();
        
        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        
        // Language
        $helper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        
        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
        $helper->submit_action = 'submit' . $this->name;
        $helper->toolbar_btn = [
            'save' => [
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex . '&configure=' . $this->name . '&save' . $this->name .
                    '&token=' . Tools::getAdminTokenLite('AdminModules'),
            ],
            'back' => [
                'href' => AdminController::$currentIndex . '&token=' . Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list')
            ]
        ];
        
        // Load current values
        $helper->fields_value = [
            static::INPUT_MODE => $settings->getMode(),
            static::INPUT_CLIENT_ID => $settings->getClientId(),
            static::INPUT_CLIENT_SECRET => $settings->getClientSecret(),
            static::INPUT_INTENT => $settings->getIntent(),
            static::INPUT_DEBUG_MODE => $settings->isDebugMode() ? 1 : 0,
        ];
        
        return $helper->generateForm([
            $this->getConfigForm(),
            $this->getWebhookForm()
        ]);
    }

    /**
     * @return array[]
     * @throws PrestaShopException
     */
    protected function getWebhookForm(): array
    {
        $webhookUrl = $this->getWebhookUrl();
        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('Webhooks'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'html',
                        'label' => $this->l('Webhook URL'),
                        'name' => 'CRON_URL',
                        'html_content' => "<code style='display:block;margin-top:7px'>$webhookUrl</code>",
                    ],
                ]
            ],
        ];
    }

    /**
     * Create Configuration Form Structure
     */
    protected function getConfigForm(): array
    {
        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('PayPal Checkout Settings'),
                    'icon' => 'icon-cog',
                ],
                'input' => [
                    [
                        'type' => 'select',
                        'label' => $this->l('Environment'),
                        'name' => static::INPUT_MODE,
                        'desc' => $this->l('Select Sandbox for testing or Live for production'),
                        'required' => true,
                        'options' => [
                            'query' => [
                                [
                                    'id' => Settings::MODE_SANDBOX,
                                    'name' => $this->l('Sandbox (Testing)')
                                ],
                                [
                                    'id' => Settings::MODE_LIVE,
                                    'name' => $this->l('Live (Production)')
                                ],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Client ID'),
                        'name' => static::INPUT_CLIENT_ID,
                        'size' => 80,
                        'required' => true,
                        'desc' => $this->l('Enter your PayPal REST API Client ID from developer.paypal.com'),
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Client Secret'),
                        'name' => static::INPUT_CLIENT_SECRET,
                        'size' => 80,
                        'required' => true,
                        'desc' => $this->l('Enter your PayPal REST API Client Secret'),
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Payment Action'),
                        'name' => static::INPUT_INTENT,
                        'desc' => $this->l('Choose when to capture payment'),
                        'options' => [
                            'query' => [
                                [
                                    'id' => Settings::INTENT_CAPTURE,
                                    'name' => $this->l('Sale - Capture payment immediately')
                                ],
                                [
                                    'id' => Settings::INTENT_AUTHORIZE,
                                    'name' => $this->l('Authorization - Capture payment later')
                                ],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Debug Mode'),
                        'name' => static::INPUT_DEBUG_MODE,
                        'desc' => $this->l('Enable detailed logging for troubleshooting'),
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            ]
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right'
                ]
            ],
        ];
    }

    /**
     * Hook: Add CSS/JS to Header
     */
    public function hookHeader()
    {
        if (!$this->active) {
            return;
        }

        // Add CSS
        $this->context->controller->addCSS($this->_path . 'views/css/paypal.css');
        
        // Add JS if on checkout page
        if ($this->context->controller->php_self === 'order' || 
            $this->context->controller->php_self === 'orderopc') {
            $this->context->controller->addJS($this->_path . 'views/js/paypal.js');
        }
    }

    /**
     * Hook: Payment Options Display
     *
     * @param $params
     * @return string|void
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookPayment($params)
    {
        if (!$this->active) {
            return;
        }

        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        $settings = $this->factory->getSettings();
        if (! $settings->validCredentials()) {
            return;
        }

        $this->smarty->assign([
            'module_dir' => $this->_path,
            'payment_url' => $this->getPaymentUrl(),
            'paypal_mode' => $settings->getMode(),
        ]);

        return $this->display(__FILE__, 'views/templates/hook/payment.tpl');
    }

    /**
     * Hook: Payment Return
     *
     * @param array $params
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookPaymentReturn($params)
    {
        if (!$this->active) {
            return '';
        }

        if (! isset($params['objOrder']) || !($params['objOrder'] instanceof Order)) {
            return '';
        }

        $order = $params['objOrder'];

        $this->smarty->assign([
            'order' => $order,
            'order_id' => (int)$order->id,
            'order_reference' => (string)$order->reference,
            'order_state' => (string)$order->getCurrentState(),
            'total_paid' => Tools::displayPrice((float)$order->total_paid, new Currency((int)$order->id_currency)),
        ]);

        return $this->display(__FILE__, 'views/templates/hook/payment_return.tpl');
    }

    /**
     * Hook: EU Payment Display
     *
     * @param array $params
     * @return array
     * @throws PrestaShopException
     */
    public function hookDisplayPaymentEU($params)
    {
        if (!$this->active) {
            return [];
        }

        $settings = $this->factory->getSettings();
        if (! $settings->validCredentials()) {
            return [];
        }

        if (!$this->checkCurrency($params['cart'])) {
            return [];
        }

        return [[
            'cta_text' => $this->l('Pay with PayPal'),
            'logo' => Media::getMediaPath(dirname(__FILE__) . '/logo.png'),
            'action' => $this->context->link->getModuleLink($this->name, 'validation', [], true)
        ]];
    }

    /**
     * Check if cart currency is supported
     *
     * @param Cart $cart
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function checkCurrency(Cart $cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ((int)$currency_order->id === (int)$currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @return Factory
     */
    public function getFactory(): Factory
    {
        return $this->factory;
    }

    /**
     * @return string
     * @throws PrestaShopException
     */
    public function getPaymentUrl(): string
    {
        return Context::getContext()->link->getModuleLink($this->name, 'checkout', [], true);
    }

    /**
     * @return string
     * @throws PrestaShopException
     */
    public function getWebhookUrl(): string
    {
        return Context::getContext()->link->getModuleLink($this->name, 'webhook', [], true);
    }
}