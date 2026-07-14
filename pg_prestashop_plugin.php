

<?php
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

include_once(_PS_MODULE_DIR_.'pg_prestashop_plugin/controllers/front/utils.php');
include_once(_PS_MODULE_DIR_.'pg_prestashop_plugin/classes/WebserviceSpecificManagementPaymentezWebhook.php');

const FLAVOR = 'Paymentez';
const FLAVOR_DOMAIN = 'paymentez.com';
const REFUND_PATH = '/v2/transaction/refund/';
const WEBHOOK_RESOURCE_NAME = 'paymentezwebhook';
const WEBHOOK_WS_CONFIG_KEY = 'PG_PRESTASHOP_PLUGIN_WEBHOOK_WS_KEY';

/**
 * PG_Prestashop_Plugin - A Payment Module for PrestaShop 8.x / 9.x
 * @author Paymentez Development <dev@paymentez.com>
 * @license http://opensource.org/licenses/afl-3.0.php
 *
 * @property \Context $context
 * @property bool $active
 * @property int $currentOrder
 * @method string l(string $string, string $specific = '')
 * @method string display(string $file, string $template)
 */
class PG_Prestashop_Plugin extends PaymentModule
{
    public function __construct()
    {
        $this->name                   = 'pg_prestashop_plugin';
        $this->tab                    = 'payments_gateways';
        $this->version                = '3.0.1';
        $this->author                 = FLAVOR.$this->l(' Development');
        $this->currencies             = true;
        $this->currencies_mode        = 'radio';
        $this->bootstrap              = true;
        $this->displayName            = FLAVOR.' Prestashop Plugin';
        $this->description            = FLAVOR.$this->l(' Payment module for process card payments.');
        $this->confirmUninstall       = $this->l('Are you sure you want to uninstall the payment module by ').FLAVOR.'?';
        $this->ps_versions_compliancy = array('min' => '8.0.0', 'max' => _PS_VERSION_);

        parent::__construct();
    }

    public function install(): bool
    {
        return parent::install()
            && $this->registerHook('header')
            && $this->registerHook('displayPaymentReturn')
            && $this->registerHook('actionProductCancel')
            && $this->registerHook('actionOrderSlipAdd')
            && $this->registerHook('displayBackOfficeHeader')
            && $this->registerHook('paymentOptions')
            && $this->registerHook('addWebserviceResources')
            && $this->installWebhookWebservice();
    }

    public function uninstall(): bool
    {
        return $this->uninstallWebhookWebservice() && parent::uninstall();
    }

    public function syncWebhookWebservice(): bool
    {
        return $this->installWebhookWebservice();
    }

    public function getContent(): string
    {
        $output = '';
        $error_messages = [];

        if (Tools::isSubmit('submit'.$this->name)) {
            $environment = strval(Tools::getValue('environment'));
            if (
                !$environment ||
                empty($environment) ||
                !Validate::isGenericName($environment)
            ) {
                array_push($error_messages, $this->l('Invalid Environment Configuration Value'));
            } else {
                Configuration::updateValue('environment', $environment);
            }

            $app_code_server = strval(Tools::getValue('app_code_server'));
            if (
                !$app_code_server ||
                empty($app_code_server) ||
                !Validate::isGenericName($app_code_server)
            ) {
                array_push($error_messages, $this->l('Invalid App Code Server Configuration Value'));
            } else {
                Configuration::updateValue('app_code_server', $app_code_server);
            }

            $app_key_server = strval(Tools::getValue('app_key_server'));
            if (
                !$app_key_server ||
                empty($app_key_server) ||
                !Validate::isGenericName($app_key_server)
            ) {
                array_push($error_messages, $this->l('Invalid App Key Server Configuration Value'));
            } else {
                Configuration::updateValue('app_key_server', $app_key_server);
            }

            $checkout_language = strval(Tools::getValue('checkout_language'));
            if (
                !$checkout_language ||
                empty($checkout_language) ||
                !Validate::isGenericName($checkout_language)
            ) {
                array_push($error_messages, $this->l('Invalid Checkout Language Configuration Value'));
            } else {
                Configuration::updateValue('checkout_language', $checkout_language);
            }

            $enable_card = strval(Tools::getValue('enable_card'));
            Configuration::updateValue('enable_card', $enable_card);

            $enable_ltp = strval(Tools::getValue('enable_ltp'));
            Configuration::updateValue('enable_ltp', $enable_ltp);

            if (!$enable_card && !$enable_ltp) {
                array_push($error_messages, $this->l('You must select at least one payment method.'));
            }

            $card_button_text = strval(Tools::getValue('card_button_text'));
            if (!$card_button_text ||
                empty($card_button_text) ||
                !Validate::isGenericName($card_button_text)
            ) {
                $card_button_text = $this->l("Pay With Card");
            }
            Configuration::updateValue('card_button_text', $card_button_text);

            $ltp_button_text = strval(Tools::getValue('ltp_button_text'));
            if (!$ltp_button_text ||
                empty($ltp_button_text) ||
                !Validate::isGenericName($ltp_button_text)
            ) {
                $ltp_button_text = $this->l("Pay With LinkToPay");
            }
            Configuration::updateValue('ltp_button_text', $ltp_button_text);

            $ltp_expiration_days = intval(Tools::getValue('ltp_expiration_days'));
            if (
                !$ltp_expiration_days ||
                empty($ltp_expiration_days) ||
                !Validate::isGenericName($ltp_expiration_days) ||
                !is_int($ltp_expiration_days)
            ) {
                array_push($error_messages, $this->l('Invalid LinkToPay Expiration Days Configuration Value'));
            } else {
                Configuration::updateValue('ltp_expiration_days', $ltp_expiration_days);
            }

            $installments_type = intval(Tools::getValue('installments_type'));
            Configuration::updateValue('installments_type', $installments_type);

            if (!$error_messages) {
                $output .= $this->displayConfirmation($this->l('Settings updated'));
            } else {
                foreach ($error_messages as $error_message) {
                    $output .= $this->displayError($this->l($error_message));
                }
            }
        }

        return $output.$this->displayForm();
    }

    public function displayForm()
    {
        // Get default language
        $defaultLang = (int)Configuration::get('PS_LANG_DEFAULT');

        // Init Fields form array
        $fieldsForm[0]['form'] = [
            'legend' => [
                'title' => $this->l('Payment Gateway Configurations: ').FLAVOR,
                'image' => 'https://cdn.paymentez.com/img/paymentez_nuvei.png'
            ],
            'input' => [
                [
                    'type'      => 'select',
                    'label'     => $this->l('Environment:'),
                    'desc'      => $this->l('Payment Gateway Environment'),
                    'name'      => 'environment',
                    'required'  => true,
                    'options'   => [
                        'query' => [
                            [
                                'id_option' => 1,
                                'name' => $this->l('Test'),
                            ],
                            [
                                'id_option' => 2,
                                'name' => $this->l('Production'),
                            ],
                        ],
                        'id'    => 'id_option',
                        'name'  => 'name',
                    ]
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('App Code Server:'),
                    'desc'     => $this->l('Unique commerce identifier to perform admin actions on ').FLAVOR,
                    'name'     => 'app_code_server',
                    'required' => true
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('App Key Server:'),
                    'desc'     => $this->l('Key used to encrypt admin communication with ').FLAVOR,
                    'name'     => 'app_key_server',
                    'required' => true
                ],
                [
                    'type'      => 'select',
                    'label'     => $this->l('Checkout Language:'),
                    'desc'      => $this->l('User\'s preferred language for checkout window. English will be used by default.'),
                    'name'      => 'checkout_language',
                    'required'  => true,
                    'options'   => [
                        'query' => [
                            [
                                'id_option' => 1,
                                'name'      => 'EN',
                            ],
                            [
                                'id_option' => 2,
                                'name'      => 'ES',
                            ],
                            [
                                'id_option' => 3,
                                'name'      => 'PT',
                            ],
                        ],
                        'id'   => 'id_option',
                        'name' => 'name',
                    ]
                ],
                [
                    'type'      => 'select',
                    'label'     => $this->l('Enable Card Payment:'),
                    'desc'      => $this->l('If selected, card can be used to pay.'),
                    'name'      => 'enable_card',
                    'required'  => true,
                    'options'   => [
                        'query' => [
                            [
                                'id_option' => 0,
                                'name'      => 'Disabled',
                            ],
                            [
                                'id_option' => 1,
                                'name'      => 'Enabled',
                            ],
                        ],
                        'id'    => 'id_option',
                        'name'  => 'name',
                    ]
                ],
                [
                    'type'      => 'select',
                    'label'     => $this->l('Enable LinkToPay:'),
                    'desc'      => $this->l('If selected, LinkToPay can be used to pay.'),
                    'name'      => 'enable_ltp',
                    'required'  => true,
                    'options'   => [
                        'query' => [
                            [
                                'id_option' => 0,
                                'name'      => 'Disabled',
                            ],
                            [
                                'id_option' => 1,
                                'name'      => 'Enabled',
                            ],
                        ],
                        'id'    => 'id_option',
                        'name'  => 'name',
                    ]
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Card Button Text:'),
                    'desc'     => $this->l('This controls the text that the user sees in the card payment button. Pay With Card is used by default.'),
                    'name'     => 'card_button_text',
                    'required' => false,
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('LinkToPay Button Text:'),
                    'desc'     => $this->l('This controls the text that the user sees in the LinkToPay payment button. Pay With LinkToPay is used by default.'),
                    'name'     => 'ltp_button_text',
                    'required' => false,
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('LinkToPay Expiration Days:'),
                    'desc'     => $this->l('This value controls the number of days that the generated LinkToPay will be available to pay.'),
                    'name'     => 'ltp_expiration_days',
                    'required' => true,
                ],
                [
                    'type'     => 'select',
                    'label'    => $this->l('Installments Type:'),
                    'desc'     => $this->l('Installments type sent to the payment gateway on card checkout. Select Disabled if not applicable.'),
                    'name'     => 'installments_type',
                    'required' => true,
                    'options'  => [
                        'query' => [
                            ['id_option' => -1, 'name' => $this->l('Disabled')],
                            ['id_option' => 0,  'name' => $this->l('Revolving credit (Colombia)')],
                            ['id_option' => 1,  'name' => $this->l('Revolving and deferred without interest (Ecuador)')],
                            ['id_option' => 2,  'name' => $this->l('Deferred with interest (Ecuador, México)')],
                            ['id_option' => 3,  'name' => $this->l('Deferred without interest (Ecuador, México)')],
                            ['id_option' => 6,  'name' => $this->l('Deferred without interest month by month (Ecuador - Medianet)')],
                            ['id_option' => 7,  'name' => $this->l('Deferred with interest and months of grace (Ecuador)')],
                            ['id_option' => 9,  'name' => $this->l('Deferred without interest and months of grace (Ecuador, México)')],
                            ['id_option' => 10, 'name' => $this->l('Deferred without interest bimonthly promotion (Ecuador - Medianet)')],
                            ['id_option' => 21, 'name' => $this->l('Diners Club deferred with/without interest (Ecuador)')],
                            ['id_option' => 22, 'name' => $this->l('Diners Club deferred with/without interest v2 (Ecuador)')],
                            ['id_option' => 30, 'name' => $this->l('Deferred with interest month by month (Ecuador - Medianet)')],
                            ['id_option' => 50, 'name' => $this->l('Deferred without interest Supermaxi promotions (Ecuador - Medianet)')],
                            ['id_option' => 51, 'name' => $this->l('Deferred with interest Cuota fácil (Ecuador - Medianet)')],
                            ['id_option' => 52, 'name' => $this->l('Without interest Redención Produmillas (Ecuador - Medianet)')],
                            ['id_option' => 53, 'name' => $this->l('Without interest sale promotions (Ecuador - Medianet)')],
                            ['id_option' => 70, 'name' => $this->l('Deferred special without interest (Ecuador - Medianet)')],
                            ['id_option' => 72, 'name' => $this->l('Credit without interest cte smax (Ecuador - Medianet)')],
                            ['id_option' => 73, 'name' => $this->l('Special credit without interest smax (Ecuador - Medianet)')],
                            ['id_option' => 74, 'name' => $this->l('Prepay without interest smax (Ecuador - Medianet)')],
                            ['id_option' => 75, 'name' => $this->l('Deferred credit without interest smax (Ecuador - Medianet)')],
                            ['id_option' => 90, 'name' => $this->l('Without interest with months of grace Supermaxi (Ecuador - Medianet)')],
                        ],
                        'id'   => 'id_option',
                        'name' => 'name',
                    ],
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right'
            ]
        ];

        $helper = new HelperForm();

        $adminModulesLink = $this->context->link->getAdminLink('AdminModules', false);

        // Module, token and currentIndex
        $helper->module          = $this;
        $helper->name_controller = $this->name;
        $helper->token           = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex    = $adminModulesLink.'&configure='.$this->name;

        // Language
        $helper->default_form_language    = $defaultLang;
        $helper->allow_employee_form_lang = $defaultLang;

        // Title and toolbar
        $helper->title          = $this->displayName;
        $helper->show_toolbar   = true;
        $helper->toolbar_scroll = true;
        $helper->submit_action  = 'submit'.$this->name;
        $helper->toolbar_btn    = [
            'save' => [
                'desc' => $this->l('Save'),
                'href' => $adminModulesLink.'&configure='.$this->name.'&save'.$this->name.
                    '&token='.Tools::getAdminTokenLite('AdminModules'),
            ],
            'back' => [
                'href' => $adminModulesLink.'&token='.Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list')
            ]
        ];

        // Load currents values
        $helper->fields_value['app_code_server']     = Tools::getValue('app_code_server', Configuration::get('app_code_server'));
        $helper->fields_value['app_key_server']      = Tools::getValue('app_key_server', Configuration::get('app_key_server'));
        $helper->fields_value['checkout_language']   = Tools::getValue('checkout_language', Configuration::get('checkout_language'));
        $helper->fields_value['environment']         = Tools::getValue('environment', Configuration::get('environment'));
        $helper->fields_value['enable_card']         = Tools::getValue('enable_card',  Configuration::get('enable_card'));
        $helper->fields_value['enable_ltp']          = Tools::getValue('enable_ltp',  Configuration::get('enable_ltp'));
        $helper->fields_value['ltp_button_text']     = Tools::getValue('ltp_button_text', Configuration::get('ltp_button_text'));
        $helper->fields_value['card_button_text']    = Tools::getValue('card_button_text', Configuration::get('card_button_text'));
        $helper->fields_value['ltp_expiration_days'] = intval(Tools::getValue('ltp_expiration_days', Configuration::get('ltp_expiration_days')));
        $helper->fields_value['installments_type']   = intval(Tools::getValue('installments_type', Configuration::get('installments_type')));

        return $helper->generateForm($fieldsForm);
    }

    public function hookPaymentOptions($params): array
    {
        if (!$this->active) {
            return [];
        }

        /**
         * Create a PaymentOption object containing the necessary data
         * to display this module in the checkout
         */

         
        $this->context->smarty->assign(array(
            'flavor' => FLAVOR,
        ));

        $actionParams = [];
        $cart = $this->context->cart;
        $customer = $this->context->customer;
        if (Validate::isLoadedObject($cart) && Validate::isLoadedObject($customer)) {
            $actionParams['pg_sig'] = PG_Prestashop_Utils::buildFrontSecuritySignature($cart, $customer);
        }

        $newOption = new PaymentOption();
        $newOption->setModuleName($this->displayName)
                  ->setCallToActionText($this->displayName)
                  ->setAction($this->context->link->getModuleLink($this->name, 'payment', $actionParams))
                  ->setAdditionalInformation($this->context->smarty->fetch('module:pg_prestashop_plugin/views/templates/front/payment_infos.tpl'));

        return [$newOption];
    }

    public function hookActionProductCancel(array $params)
    {
        $this->processRefundRequest($params);
    }

    public function hookActionOrderSlipAdd(array $params)
    {
        $this->processRefundRequest($params);
    }

    private function processRefundRequest(array $params): void
    {
        if ((int)($params['action'] ?? 1) !== 1 && empty($_POST['cancel_product'])) {
            return;
        }

        $order = $this->resolveRefundOrder($params);
        if (!$order instanceof Order || !Validate::isLoadedObject($order)) {
            return;
        }

        if (!PG_Prestashop_Utils::isCardRefundableOrder($order)) {
            PrestaShopLogger::addLog('Paymentez refund skipped: only card payments are refundable.', 3);
            return;
        }

        $amount_to_refund = $this->resolveRefundAmount($params, $order);

        if ($amount_to_refund <= 0) {
            PrestaShopLogger::addLog('Paymentez refund skipped: refund amount is zero.', 3);
            return;
        }

        $transaction_id = $this->getOrderTransactionId($order);
        if ($transaction_id === '') {
            PrestaShopLogger::addLog('Paymentez refund skipped: transaction id not found.', 3);
            return;
        }

        $response = $this->sendRefundToPaymentez($transaction_id, $amount_to_refund);
        if ($response === null) {
            return;
        }

        $history           = new OrderHistory();
        $history->id_order = (int) $order->id;
        $history->changeIdOrderState($this->getRefundOrderStateId(), (int) $order->id);
        $history->save();
    }

    private function resolveRefundOrder(array $params)
    {
        if (isset($params['order']) && $params['order'] instanceof Order) {
            return $params['order'];
        }

        if (isset($params['order']) && is_object($params['order']) && !empty($params['order']->id)) {
            return new Order((int) $params['order']->id);
        }

        $idOrder = (int) (
            $params['id_order']
            ?? $_POST['id_order']
            ?? Tools::getValue('id_order')
            ?? 0
        );
        if ($idOrder > 0) {
            return new Order($idOrder);
        }

        return null;
    }

    private function resolveRefundAmount(array $params, Order $order): float
    {
        $cancel_product = (array) ($_POST['cancel_product'] ?? []);
        if (!empty($cancel_product)) {
            return $this->calculateRefundAmount($cancel_product, $order);
        }

        foreach ([
            'amount_to_refund',
            'refund_amount',
            'amount',
            'total',
        ] as $key) {
            if (isset($params[$key]) && is_numeric($params[$key])) {
                return (float) $params[$key];
            }
        }

        if (isset($params['order_slip']) && is_object($params['order_slip'])) {
            foreach (['amount', 'total', 'refund_amount'] as $property) {
                if (isset($params['order_slip']->{$property}) && is_numeric($params['order_slip']->{$property})) {
                    return (float) $params['order_slip']->{$property};
                }
            }
        }

        if (isset($params['order_slip']) && is_array($params['order_slip'])) {
            foreach (['amount', 'total', 'refund_amount'] as $property) {
                if (isset($params['order_slip'][$property]) && is_numeric($params['order_slip'][$property])) {
                    return (float) $params['order_slip'][$property];
                }
            }
        }

        if (!empty($params['productList']) && is_array($params['productList'])) {
            $amount = 0.0;
            foreach ($params['productList'] as $product) {
                if (!is_array($product)) {
                    continue;
                }

                $unitPrice = (float) ($product['unit_price_tax_incl'] ?? $product['price'] ?? $product['price_wt'] ?? 0);
                $quantity = (float) ($product['refund_quantity'] ?? $product['quantity'] ?? 1);
                $amount += $unitPrice * $quantity;
            }

            if ($amount > 0) {
                return $amount;
            }
        }

        if (!empty($params['qtyList']) && is_array($params['qtyList']) && !empty($params['productList']) && is_array($params['productList'])) {
            $amount = 0.0;
            foreach ($params['qtyList'] as $idOrderDetail => $quantity) {
                foreach ($params['productList'] as $product) {
                    $productId = $product['id_order_detail'] ?? $product['id'] ?? null;
                    if ((string) $productId !== (string) $idOrderDetail) {
                        continue;
                    }

                    $unitPrice = (float) ($product['unit_price_tax_incl'] ?? $product['price'] ?? $product['price_wt'] ?? 0);
                    $amount += $unitPrice * (float) $quantity;
                    break;
                }
            }

            if ($amount > 0) {
                return $amount;
            }
        }

        return 0.0;
    }

    private function sendRefundToPaymentez(string $transaction_id, float $amount_to_refund): ?array
    {
        $environment = Configuration::get('environment');
        $url = ($environment == 1)
            ? 'https://ccapi-stg.' . FLAVOR_DOMAIN . REFUND_PATH
            : 'https://ccapi.' . FLAVOR_DOMAIN . REFUND_PATH;

        $app_code_server = Configuration::get('app_code_server');
        $app_key_server  = Configuration::get('app_key_server');
        $refund_data = [
            'transaction' => ['id' => $transaction_id],
            'order' => ['amount' => round($amount_to_refund, 2, PHP_ROUND_HALF_DOWN)]
        ];
        $payload = json_encode($refund_data);

        $timestamp         = (string) time();
        $uniq_token_string = $app_key_server . $timestamp;
        $uniq_token_hash   = hash('sha256', $uniq_token_string);
        $auth_token        = base64_encode($app_code_server . ';' . $timestamp . ';' . $uniq_token_hash);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type:application/json',
            'Auth-Token:' . $auth_token
        ]);
        $response = curl_exec($ch);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($response === false || $curl_error !== '') {
            PrestaShopLogger::addLog('Paymentez refund cURL error: ' . $curl_error, 3);
            return null;
        }

        $get_response = json_decode($response, true);
        if (!is_array($get_response) || !empty($get_response['error']) || (($get_response['status'] ?? '') == 'failure')) {
            PrestaShopLogger::addLog('Paymentez refund failed. Response: ' . (string) $response, 3);
            return null;
        }

        return $get_response;
    }

    private function calculateRefundAmount(array $cancel_product, Order $order): float
    {
        if ($this->isFullRefundRequest($cancel_product)) {
            return (float) $order->total_paid;
        }

        $amount_to_refund = 0;
        if (isset($cancel_product['shipping'])) {
            $amount_to_refund += (float) $order->total_shipping;
        }

        $keys_pop = ['_token', 'save', 'voucher_refund_type', 'voucher', 'credit_slip', 'shipping_amount', 'shipping'];
        foreach ($keys_pop as $key) {
            unset($cancel_product[$key]);
        }

        $selected = [];
        $quantity = [];
        foreach (array_keys($cancel_product) as $key) {
            if (strpos($key, 'selected') !== false) {
                $id_order_detail = (string) explode('_', $key)[1];
                $selected[$id_order_detail] = $cancel_product[$key];
            } elseif (strpos($key, 'quantity') !== false) {
                $id_order_detail = (string) explode('_', $key)[1];
                $quantity[$id_order_detail] = $cancel_product[$key];
            }
        }

        foreach ($selected as $key => $value) {
            if ($value) {
                $order_detail = new OrderDetail((int) $key);
                $amount_to_refund += ((float) ($quantity[$key] ?? 0)) * (float) $order_detail->unit_price_tax_incl;
            }
        }

        return $amount_to_refund;
    }

    private function isFullRefundRequest(array $cancel_product): bool
    {
        return !empty($cancel_product['credit_slip']) && (string) $cancel_product['credit_slip'] !== '0';
    }

    private function getOrderTransactionId(Order $order): string
    {
        $collection = OrderPayment::getByOrderReference($order->reference);
        if (count($collection) === 0) {
            return '';
        }

        foreach ($collection as $order_payment) {
            if ($order_payment->payment_method == FLAVOR . ' Prestashop Plugin') {
                return (string) $order_payment->transaction_id;
            }
        }

        return '';
    }

    private function getRefundOrderStateId(): int
    {
        $refundStateId = (int) Configuration::get('PS_OS_REFUND');
        return $refundStateId > 0 ? $refundStateId : 7;
    }

    public function hookHeader()
    {
        $this->context->controller->registerStylesheet(
            'front-css',
            'modules/' . $this->name . '/views/css/main.css'
        );
    }

    public function hookDisplayBackOfficeHeader(): string
    {
        $idOrder = (int) Tools::getValue('id_order');
        if ($idOrder <= 0) {
            return '';
        }

        $order = new Order($idOrder);
        if (!Validate::isLoadedObject($order) || !PG_Prestashop_Utils::isLinkToPayOrder($order)) {
            return '';
        }

        return '<script>
            document.addEventListener("DOMContentLoaded", function () {
                var nodes = document.querySelectorAll("button, input[type=\\"submit\\"], a.btn");
                nodes.forEach(function (node) {
                    var text = (node.innerText || node.value || "").toLowerCase();
                    if (text.indexOf("refund") !== -1 || text.indexOf("reembolso") !== -1) {
                        node.style.display = "none";
                    }
                });
            });
        </script>';
    }

    public function hookDisplayPaymentReturn($params): string
    {
        if (!$this->active) {
            return '';
        }

        $transaction_id = '';
        $collection = OrderPayment::getByOrderReference($params['order']->reference);
        if (count($collection) > 0)
        {
            foreach ($collection as $order_payment)
            {
                $transaction_id = $order_payment->transaction_id;
            }
        }
        
        $pg_approved = ($this->context->cookie->pg_payment_approved === '1');
        unset($this->context->cookie->pg_payment_approved);

        $this->context->smarty->assign([
            'payment_id'          => $transaction_id,
            'module_gtw'          => $this->displayName,
            'pg_payment_approved' => $pg_approved,
        ]);
        return $this->display(__FILE__, 'views/templates/hook/payment_return.tpl');
    }

    public function hookAddWebserviceResources(): array
    {
        return array(
            WEBHOOK_RESOURCE_NAME => array(
                'description'         => FLAVOR.' Webhook Management.',
                'specific_management' => true
            )
        );
    }

    private function installWebhookWebservice(): bool
    {
        try {
            $permissions = $this->getWebhookPermissions();
            $storedKey = (string) Configuration::get(WEBHOOK_WS_CONFIG_KEY);
            if ($storedKey !== '') {
                $accountId = (int) WebserviceKey::getIdFromKey($storedKey);
                if ($accountId > 0) {
                    return WebserviceKey::setPermissionForAccount($accountId, $permissions);
                }
            }

            $generatedKey = bin2hex(random_bytes(16));

            $account = new WebserviceKey();
            $account->key = $generatedKey;
            $account->description = FLAVOR . ' webhook';
            $account->active = true;

            if (!$account->add()) {
                PrestaShopLogger::addLog('Paymentez could not create the webhook webservice key.', 3);
                return false;
            }

            if (!WebserviceKey::setPermissionForAccount((int) $account->id, $permissions)) {
                $account->delete();
                PrestaShopLogger::addLog('Paymentez could not configure webhook permissions.', 3);
                return false;
            }

            return Configuration::updateValue(WEBHOOK_WS_CONFIG_KEY, $generatedKey);
        } catch (Exception $e) {
            PrestaShopLogger::addLog('Paymentez webhook setup failed: ' . $e->getMessage(), 3);
            return false;
        }
    }

    private function getWebhookPermissions(): array
    {
        return [
            WEBHOOK_RESOURCE_NAME => [
                'POST' => true,
            ],
        ];
    }

    private function uninstallWebhookWebservice(): bool
    {
        $storedKey = (string) Configuration::get(WEBHOOK_WS_CONFIG_KEY);
        if ($storedKey !== '') {
            $accountId = (int) WebserviceKey::getIdFromKey($storedKey);
            if ($accountId > 0) {
                $account = new WebserviceKey($accountId);
                if ($account->id) {
                    $account->delete();
                }
            }
        }

        if ($storedKey !== '') {
            return Configuration::deleteByName(WEBHOOK_WS_CONFIG_KEY);
        }

        return true;
    }
}