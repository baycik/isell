<?php

class ControllerCheckoutCheckout extends Controller {

    public function __construct($registry) {
        parent::__construct($registry);
        $this->load->language('checkout/cart');
        $this->load->language('checkout/checkout');

        $this->load->language('checkout/coupon');
        $this->load->language('checkout/voucher');
        $this->load->language('extension/module/so_onepagecheckout');

        $this->load->model('account/activity');
        $this->load->model('account/custom_field');
        $this->load->model('tool/upload');

        $this->load->model('account/address');
        $this->load->model('account/customer');
        $this->load->model('account/customer_group');
        $this->load->model('localisation/country');
        $this->load->model('localisation/zone');
        $this->load->model('extension/module/so_onepagecheckout');
    }

    public function getProperty($array, $property, $default_value = null) {
        $properties = explode('.', $property);
        foreach ($properties as $prop) {
            if (!is_array($array) || !isset($array[$prop])) {
                return $default_value;
            }
            $array = $array[$prop];
        }
        if (is_array($array)) {
            return $array;
        }
        $array = trim($array);
        return $array !== '' ? $array : $default_value;
    }

    private function isShippingRequired() {
        return $this->cart->hasShipping();
    }

    private function isLoggedIn() {
        return $this->customer->isLogged();
    }

    private function allowGuestCheckout() {
        return $this->config->get('config_checkout_guest') && !$this->config->get('config_customer_price') && !$this->cart->hasDownload();
    }

    private function renderRegisterForm() {
        $this->load->model('setting/setting');
        $setting_so_onepagecheckout = $this->model_setting_setting->getSetting('so_onepagecheckout');
        $setting_so_onepagecheckout_general = $setting_so_onepagecheckout['so_onepagecheckout_general'];
        $setting_so_onepagecheckout_layout_setting = $setting_so_onepagecheckout['so_onepagecheckout_layout_setting'];
        $data['setting_so_onepagecheckout'] = $setting_so_onepagecheckout;
        $data['setting_so_onepagecheckout_general'] = $setting_so_onepagecheckout_general;
        $data['setting_so_onepagecheckout_layout_setting'] = $setting_so_onepagecheckout_layout_setting;

        $data['text_register'] = $this->language->get('text_register');
        $data['text_guest'] = $this->language->get('text_guest');
        $data['entry_email'] = $this->language->get('entry_email');
        $data['entry_password'] = $this->language->get('entry_password');
        $data['text_forgotten'] = $this->language->get('text_forgotten');
        $data['text_loading'] = $this->language->get('text_loading');
        $data['button_login'] = $this->language->get('button_login');
        $data['text_i_am_returning_customer'] = $this->language->get('text_i_am_returning_customer');
        $data['text_returning_customer'] = $this->language->get('text_returning_customer');

        $data['text_your_details'] = $this->language->get('text_your_details');
        $data['entry_customer_group'] = $this->language->get('entry_customer_group');
        $data['entry_firstname'] = $this->language->get('entry_firstname');
        $data['entry_lastname'] = $this->language->get('entry_lastname');
        $data['entry_telephone'] = $this->language->get('entry_telephone');
        $data['entry_fax'] = $this->language->get('entry_fax');
        $data['text_your_password'] = $this->language->get('text_your_password');
        $data['entry_confirm'] = $this->language->get('entry_confirm');
        $data['text_your_address'] = $this->language->get('text_your_address');
        $data['entry_shipping'] = $this->language->get('entry_shipping');
        $data['text_shipping_address'] = $this->language->get('text_shipping_address');

        $data['customer_groups'] = array();
        $data['customer_group_id'] = $this->model_extension_module_so_onepagecheckout->getCustomerGroupId();
        if (is_array($this->config->get('config_customer_group_display'))) {
            $this->load->model('account/customer_group');

            $customer_groups = $this->model_account_customer_group->getCustomerGroups();

            foreach ($customer_groups as $customer_group) {
                if (in_array($customer_group['customer_group_id'], $this->config->get('config_customer_group_display'))) {
                    $data['customer_groups'][] = $customer_group;
                }
            }
        }

        $data['payment_address_form'] = $this->renderAddressForm('payment', false);
        $data['shipping_address_form'] = $this->renderAddressForm('shipping');
        $data['shipping_address'] = $this->getProperty($this->session->data, 'so_checkout_shipping_address', '1');
        $data['is_shipping_required'] = $this->isShippingRequired();

        $data['forgotten'] = $this->url->link('account/forgotten', '', true);

        $data['custom_fields'] = $this->model_extension_module_so_onepagecheckout->getCustomFields();
        $data['order_data'] = $this->model_extension_module_so_onepagecheckout->getOrder();

        return $this->load->view('extension/module/so_onepagecheckout/checkout/register', $data);
    }

    private function renderAddressForm($type, $name = true) {
        $this->load->model('setting/setting');
        $setting_so_onepagecheckout = $this->model_setting_setting->getSetting('so_onepagecheckout');
        $setting_so_onepagecheckout_general = $setting_so_onepagecheckout['so_onepagecheckout_general'];
        $setting_so_onepagecheckout_layout_setting = $setting_so_onepagecheckout['so_onepagecheckout_layout_setting'];

        $data['type'] = $type;
        $data['name'] = $name;

        $data['button_upload'] = $this->language->get('button_upload');
        $data['text_address_existing'] = $this->language->get('text_address_existing');
        $data['text_address_new'] = $this->language->get('text_address_new');
        $data['text_select'] = $this->language->get('text_select');
        $data['text_none'] = $this->language->get('text_none');

        $data['entry_firstname'] = $this->language->get('entry_firstname');
        $data['entry_lastname'] = $this->language->get('entry_lastname');
        $data['entry_company'] = $this->language->get('entry_company');
        $data['entry_company_id'] = $this->language->get('entry_company_id');
        $data['entry_tax_id'] = $this->language->get('entry_tax_id');
        $data['entry_address_1'] = $this->language->get('entry_address_1');
        $data['entry_address_2'] = $this->language->get('entry_address_2');
        $data['entry_postcode'] = $this->language->get('entry_postcode');
        $data['entry_city'] = $this->language->get('entry_city');
        $data['entry_country'] = $this->language->get('entry_country');
        $data['entry_zone'] = $this->language->get('entry_zone');

        $data['custom_fields'] = $this->model_extension_module_so_onepagecheckout->getCustomFields($type);
        $data['order_data'] = $this->model_extension_module_so_onepagecheckout->getOrder();

        $data['addresses'] = $this->model_account_address->getAddresses();
        $data['countries'] = $this->model_localisation_country->getCountries();

        $address = $this->model_extension_module_so_onepagecheckout->getAddress($type);
        foreach ($address as $key => $value) {
            $data[$key] = $value;
        }

        if ($this->isLoggedIn()) {
            $data['is_logged_in'] = true;
        } else {
            $data['is_logged_in'] = false;
        }

        if (isset($setting_so_onepagecheckout_general['so_onepagecheckout_country_id']) && $setting_so_onepagecheckout_general['so_onepagecheckout_country_id']) {
            $data['country_id'] = $setting_so_onepagecheckout_general['so_onepagecheckout_country_id'];
        }

        if (isset($setting_so_onepagecheckout_general['so_onepagecheckout_zone_id']) && $setting_so_onepagecheckout_general['so_onepagecheckout_zone_id']) {
            $data['zone_id'] = $setting_so_onepagecheckout_general['so_onepagecheckout_zone_id'];
        }

        return $this->load->view('extension/module/so_onepagecheckout/checkout/address_form', $data);
    }

    public function shipping($load = false) {
        $this->load->model('setting/setting');
        $setting_so_onepagecheckout = $this->model_setting_setting->getSetting('so_onepagecheckout');
        $setting_so_onepagecheckout_general = $setting_so_onepagecheckout['so_onepagecheckout_general'];
        $setting_so_onepagecheckout_layout_setting = $setting_so_onepagecheckout['so_onepagecheckout_layout_setting'];
        $data['setting_so_onepagecheckout'] = $setting_so_onepagecheckout;
        $data['setting_so_onepagecheckout_general'] = $setting_so_onepagecheckout_general;
        $data['setting_so_onepagecheckout_layout_setting'] = $setting_so_onepagecheckout_layout_setting;

        $data['text_shipping_method'] = $this->language->get('text_shipping_method');
        $data['text_title_shipping_method'] = $this->language->get('text_title_shipping_method');
        $data['error_shipping_method'] = $this->language->get('error_shipping_method');

        $data['shipping_methods'] = $this->model_extension_module_so_onepagecheckout->getShippingMethods();
        $shipping_methods = array();
        if (count($data['shipping_methods']) > 0) {
            foreach ($data['shipping_methods'] as $key => $shipping_method) {
                if (@$setting_so_onepagecheckout_layout_setting[$key . '_status'] == 1) {
                    $shipping_methods[] = $shipping_method;
                }
            }
        }
        $data['shipping_methods'] = $shipping_methods;
        $data['code'] = $this->model_extension_module_so_onepagecheckout->getShippingMethodCode();

        if (!$data['shipping_methods']) {
            $data['error_warning'] = sprintf($this->language->get('error_no_shipping'), $this->url->link('information/contact', '', true));
        } else {
            $data['error_warning'] = '';
        }

        if ($load)
            return $this->load->view('extension/module/so_onepagecheckout/checkout/shipping_methods', $data);
        else
            return $this->response->setOutput($this->load->view('extension/module/so_onepagecheckout/checkout/shipping_methods', $data));
    }

    public function payment($load = false) {
        $this->load->model('setting/setting');
        $setting_so_onepagecheckout = $this->model_setting_setting->getSetting('so_onepagecheckout');
        $setting_so_onepagecheckout_general = $setting_so_onepagecheckout['so_onepagecheckout_general'];
        $setting_so_onepagecheckout_layout_setting = $setting_so_onepagecheckout['so_onepagecheckout_layout_setting'];
        $data['setting_so_onepagecheckout'] = $setting_so_onepagecheckout;
        $data['setting_so_onepagecheckout_general'] = $setting_so_onepagecheckout_general;
        $data['setting_so_onepagecheckout_layout_setting'] = $setting_so_onepagecheckout_layout_setting;

        $data['text_payment_method'] = $this->language->get('text_payment_method');
        $data['text_title_payment_method'] = $this->language->get('text_title_payment_method');

        $data['payment_methods'] = $this->model_extension_module_so_onepagecheckout->getPaymentMethods();
        $payment_methods = array();
        if (count($data['payment_methods']) > 0) {
            foreach ($data['payment_methods'] as $key => $payment_method) {
                if (@$setting_so_onepagecheckout_layout_setting[$key . '_status'] == 1) {
                    $payment_methods[] = $payment_method;
                }
            }
        }
        $data['payment_methods'] = $payment_methods;
        $data['code'] = $this->model_extension_module_so_onepagecheckout->getPaymentMethodCode();

        if (!$data['payment_methods']) {
            $data['error_warning'] = sprintf($this->language->get('error_no_payment'), $this->url->link('information/contact', '', true));
        } else {
            $data['error_warning'] = '';
        }

        if ($load)
            return $this->load->view('extension/module/so_onepagecheckout/checkout/payment_methods', $data);
        else
            return $this->response->setOutput($this->load->view('extension/module/so_onepagecheckout/checkout/payment_methods', $data));
    }

    public function change_coupon_voucher_reward() {
        $this->load->model('setting/setting');
        $this->load->language('checkout/cart');
        $this->load->language('checkout/checkout');
        $this->load->language('extension/module/so_onepagecheckout');
        $setting_so_onepagecheckout = $this->model_setting_setting->getSetting('so_onepagecheckout');
        $setting_so_onepagecheckout_general = $setting_so_onepagecheckout['so_onepagecheckout_general'];
        $setting_so_onepagecheckout_layout_setting = $setting_so_onepagecheckout['so_onepagecheckout_layout_setting'];
        $data['setting_so_onepagecheckout'] = $setting_so_onepagecheckout;
        $data['setting_so_onepagecheckout_general'] = $setting_so_onepagecheckout_general;
        $data['setting_so_onepagecheckout_layout_setting'] = $setting_so_onepagecheckout_layout_setting;

        $data['text_loading'] = $this->language->get('text_loading');
        $data['text_coupon_voucher'] = $this->language->get('text_coupon_voucher');
        $data['text_enter_coupon_code'] = $this->language->get('text_enter_coupon_code');
        $data['text_enter_voucher_code'] = $this->language->get('text_enter_voucher_code');
        $data['text_apply_voucher'] = $this->language->get('text_apply_voucher');
        $data['text_enter_reward_points'] = $this->language->get('text_enter_reward_points');
        $data['text_apply_points'] = $this->language->get('text_apply_points');

        $_data = $this->request->post;

        if (@$_data['so_checkout_account'] == 'login') {
            $data['coupon_status'] = $this->config->get('total_coupon_status') && $setting_so_onepagecheckout_layout_setting['coupon_login_status'];
        } else if (@$_data['so_checkout_account'] == 'register') {
            $data['coupon_status'] = $this->config->get('total_coupon_status') && $setting_so_onepagecheckout_layout_setting['coupon_register_status'];
        } else if (@$_data['so_checkout_account'] == 'guest') {
            $data['coupon_status'] = $this->config->get('total_coupon_status') && $setting_so_onepagecheckout_layout_setting['coupon_guest_status'];
        } else {
            $data['coupon_status'] = $this->config->get('total_coupon_status');
        }
        $data['entry_coupon'] = $this->language->get('entry_coupon');
        $data['button_coupon'] = $this->language->get('button_coupon');
        $data['coupon'] = $this->getProperty($this->session->data, 'coupon');

        if (@$_data['so_checkout_account'] == 'login') {
            $data['voucher_status'] = $this->config->get('total_voucher_status') && $setting_so_onepagecheckout_layout_setting['voucher_login_status'];
        } else if (@$_data['so_checkout_account'] == 'register') {
            $data['voucher_status'] = $this->config->get('total_voucher_status') && $setting_so_onepagecheckout_layout_setting['voucher_register_status'];
        } else if (@$_data['so_checkout_account'] == 'guest') {
            $data['voucher_status'] = $this->config->get('total_voucher_status') && $setting_so_onepagecheckout_layout_setting['voucher_guest_status'];
        } else {
            $data['voucher_status'] = $this->config->get('total_voucher_status');
        }
        $data['entry_voucher'] = $this->language->get('entry_voucher');
        $data['button_voucher'] = $this->language->get('button_voucher');
        $data['voucher'] = $this->getProperty($this->session->data, 'voucher');

        $points = $this->customer->getRewardPoints();

        $points_total = 0;

        foreach ($this->cart->getProducts() as $product) {
            if ($product['points']) {
                $points_total += $product['points'];
            }
        }

        if (@$_data['so_checkout_account'] == 'login') {
            $data['reward_status'] = $points && $points_total && $this->config->get('total_reward_status') && $setting_so_onepagecheckout_layout_setting['reward_login_status'];
        } else if (@$_data['so_checkout_account'] == 'register') {
            $data['reward_status'] = $points && $points_total && $this->config->get('total_reward_status') && $setting_so_onepagecheckout_layout_setting['reward_register_status'];
        } else if (@$_data['so_checkout_account'] == 'guest') {
            $data['reward_status'] = $points && $points_total && $this->config->get('total_reward_status') && $setting_so_onepagecheckout_layout_setting['reward_guest_status'];
        } else {
            $data['reward_status'] = $points && $points_total && $this->config->get('total_reward_status');
        }
        $data['entry_reward'] = $this->language->get('entry_reward');
        $data['button_reward'] = $this->language->get('button_reward');
        $data['reward'] = $this->getProperty($this->session->data, 'reward');

        echo $this->load->view('extension/module/so_onepagecheckout/checkout/coupon_voucher_reward', $data);
        exit();
    }

    public function save() {
        $this->load->model('extension/module/so_onepagecheckout');
        $this->load->model('account/address');
        if ($value = $this->getProperty($this->request->post, 'shipping_address_id')) {
            $this->session->data['shipping_address'] = $this->model_account_address->getAddress($value);
            $this->model_extension_module_so_onepagecheckout->setAddress('shipping', $this->session->data['shipping_address']);
        }

        if ($value = $this->getProperty($this->request->post, 'shipping_country_id')) {
            $this->model_extension_module_so_onepagecheckout->setAddress('shipping', array(
                'country_id' => $value,
                'zone_id' => $this->getProperty($this->request->post, 'shipping_zone_id'),
                'postcode' => $this->getProperty($this->request->post, 'shipping_postcode'),
            ));
        }

        if ($value = $this->getProperty($this->request->post, 'shipping_method')) {
            $shipping = explode('.', $value);
            $this->session->data['shipping_method'] = $this->session->data['shipping_methods'][$shipping[0]]['quote'][$shipping[1]];
        }

        if ($value = $this->getProperty($this->request->post, 'payment_address_id')) {
            $this->session->data['payment_address'] = $this->model_account_address->getAddress($value);
            $this->model_extension_module_so_onepagecheckout->setAddress('payment', $this->session->data['payment_address']);
        }

        if ($value = $this->getProperty($this->request->post, 'payment_country_id')) {
            $this->model_extension_module_so_onepagecheckout->setAddress('payment', array(
                'country_id' => $value,
                'zone_id' => $this->getProperty($this->request->post, 'payment_zone_id'),
                'postcode' => $this->getProperty($this->request->post, 'payment_postcode'),
            ));
        }

        if ($value = $this->getProperty($this->request->post, 'payment_method')) {
            $this->session->data['payment_method'] = $this->session->data['payment_methods'][$value];
        }

        $this->model_extension_module_so_onepagecheckout->save();

        header('Content-Type: application/json');
        echo '{}';

        exit;
    }

    private function renderCouponVoucherReward() {
        $this->load->model('setting/setting');
        $setting_so_onepagecheckout = $this->model_setting_setting->getSetting('so_onepagecheckout');
        $setting_so_onepagecheckout_general = $setting_so_onepagecheckout['so_onepagecheckout_general'];
        $setting_so_onepagecheckout_layout_setting = $setting_so_onepagecheckout['so_onepagecheckout_layout_setting'];
        $data['setting_so_onepagecheckout'] = $setting_so_onepagecheckout;
        $data['setting_so_onepagecheckout_general'] = $setting_so_onepagecheckout_general;
        $data['setting_so_onepagecheckout_layout_setting'] = $setting_so_onepagecheckout_layout_setting;

        $data['text_loading'] = $this->language->get('text_loading');
        $data['text_coupon_voucher'] = $this->language->get('text_coupon_voucher');
        $data['text_enter_coupon_code'] = $this->language->get('text_enter_coupon_code');
        $data['text_enter_voucher_code'] = $this->language->get('text_enter_voucher_code');
        $data['text_apply_voucher'] = $this->language->get('text_apply_voucher');
        $data['text_enter_reward_points'] = $this->language->get('text_enter_reward_points');
        $data['text_apply_points'] = $this->language->get('text_apply_points');

        $_data = $this->session->data;

        if (@$_data['so_checkout_account'] == 'login') {
            $data['coupon_status'] = $this->config->get('total_coupon_status') && $setting_so_onepagecheckout_layout_setting['coupon_login_status'];
        } else if (@$_data['so_checkout_account'] == 'register') {
            $data['coupon_status'] = $this->config->get('total_coupon_status') && $setting_so_onepagecheckout_layout_setting['coupon_register_status'];
        } else if (@$_data['so_checkout_account'] == 'guest') {
            $data['coupon_status'] = $this->config->get('total_coupon_status') && $setting_so_onepagecheckout_layout_setting['coupon_guest_status'];
        } else {
            $data['coupon_status'] = $this->config->get('total_coupon_status');
        }
        $data['entry_coupon'] = $this->language->get('entry_coupon');
        $data['button_coupon'] = $this->language->get('button_coupon');
        $data['coupon'] = $this->getProperty($this->session->data, 'coupon');

        if (@$_data['so_checkout_account'] == 'login') {
            $data['voucher_status'] = $this->config->get('total_voucher_status') && $setting_so_onepagecheckout_layout_setting['voucher_login_status'];
        } else if (@$_data['so_checkout_account'] == 'register') {
            $data['voucher_status'] = $this->config->get('total_voucher_status') && $setting_so_onepagecheckout_layout_setting['voucher_register_status'];
        } else if (@$_data['so_checkout_account'] == 'guest') {
            $data['voucher_status'] = $this->config->get('total_voucher_status') && $setting_so_onepagecheckout_layout_setting['voucher_guest_status'];
        } else {
            $data['voucher_status'] = $this->config->get('total_voucher_status');
        }
        $data['entry_voucher'] = $this->language->get('entry_voucher');
        $data['button_voucher'] = $this->language->get('button_voucher');
        $data['voucher'] = $this->getProperty($this->session->data, 'voucher');

        $points = $this->customer->getRewardPoints();

        $points_total = 0;

        foreach ($this->cart->getProducts() as $product) {
            if ($product['points']) {
                $points_total += $product['points'];
            }
        }

        if (@$_data['so_checkout_account'] == 'login') {
            $data['reward_status'] = $points && $points_total && $this->config->get('total_reward_status') && $setting_so_onepagecheckout_layout_setting['reward_login_status'];
        } else if (@$_data['so_checkout_account'] == 'register') {
            $data['reward_status'] = $points && $points_total && $this->config->get('total_reward_status') && $setting_so_onepagecheckout_layout_setting['reward_register_status'];
        } else if (@$_data['so_checkout_account'] == 'guest') {
            $data['reward_status'] = $points && $points_total && $this->config->get('total_reward_status') && $setting_so_onepagecheckout_layout_setting['reward_guest_status'];
        } else {
            $data['reward_status'] = $points && $points_total && $this->config->get('total_reward_status');
        }
        $data['entry_reward'] = $this->language->get('entry_reward');
        $data['button_reward'] = $this->language->get('button_reward');
        $data['reward'] = $this->getProperty($this->session->data, 'reward');

        return $this->load->view('extension/module/so_onepagecheckout/checkout/coupon_voucher_reward', $data);
    }

    public function cart($load = false) {
        $this->load->model('setting/setting');
        $this->load->model('extension/module/so_onepagecheckout');
        $this->load->language('checkout/cart');
        $this->load->language('checkout/checkout');
        $this->load->language('extension/module/so_onepagecheckout');
        $setting_so_onepagecheckout = $this->model_setting_setting->getSetting('so_onepagecheckout');
        $setting_so_onepagecheckout_general = $setting_so_onepagecheckout['so_onepagecheckout_general'];
        $setting_so_onepagecheckout_layout_setting = $setting_so_onepagecheckout['so_onepagecheckout_layout_setting'];

        $data['text_recurring_item'] = $this->language->get('text_recurring_item');
        $data['text_payment_recurring'] = $this->language->get('text_payment_recurring');
        $data['text_shopping_cart'] = $this->language->get('text_shopping_cart');
        $data['text_payment_detail'] = $this->language->get('text_payment_detail');

        $data['button_update'] = $this->language->get('button_update');
        $data['button_remove'] = $this->language->get('button_remove');

        $data['column_image'] = $this->language->get('column_image');
        $data['column_name'] = $this->language->get('column_name');
        $data['column_model'] = $this->language->get('column_model');
        $data['column_quantity'] = $this->language->get('column_quantity');
        $data['column_price'] = $this->language->get('column_price');
        $data['column_total'] = $this->language->get('column_total');

        $data['products'] = $this->model_extension_module_so_onepagecheckout->getProducts();
        $data['vouchers'] = $this->model_extension_module_so_onepagecheckout->getVouchers();
        $data['totals'] = $this->model_extension_module_so_onepagecheckout->getTotals();

        if ($value = $this->getProperty($this->session->data, 'payment_method.code')) {
            $data['payment'] = $this->load->controller('extension/payment/' . $value);
        } else {
            $data['payment'] = '';
        }

        $data['session_data'] = $this->session->data;

        if ($this->config->get('config_cart_weight') && (isset($setting_so_onepagecheckout_layout_setting['show_product_weight']) && $setting_so_onepagecheckout_layout_setting['show_product_weight'])) {
            $data['weight'] = $this->weight->format($this->cart->getWeight(), $this->config->get('config_weight_class_id'), $this->language->get('decimal_point'), $this->language->get('thousand_point'));
        } else {
            $data['weight'] = '';
        }

        $data['setting_so_onepagecheckout_layout_setting'] = $setting_so_onepagecheckout_layout_setting;

        if ($load)
            return $this->load->view('extension/module/so_onepagecheckout/checkout/cart', $data);
        else
            return $this->response->setOutput($this->load->view('extension/module/so_onepagecheckout/checkout/cart', $data));
    }

    public function cart_update() {
        $this->load->language('checkout/cart');
        $session_update = $this->getProperty($this->request->post, 'key');
        $qty = $this->getProperty($this->request->post, 'quantity');
        $this->cart->update($session_update, $qty);

        $json = array();

        if (!$this->checkCart()) {
            $json['redirect'] = $this->url->link('checkout/cart', '', true);
        } else {
            $json['total'] = sprintf($this->language->get('text_items'), $this->cart->countProducts() + (isset($this->session->data['vouchers']) ? count($this->session->data['vouchers']) : 0), $this->registry->get('currency')->format($this->model_extension_module_so_onepagecheckout->getTotal(), $this->session->data['currency']));
        }

        echo json_encode($json);
        exit;
    }

    public function cart_delete() {
        $session_delete = $this->getProperty($this->request->post, 'key');

        $this->cart->remove($session_delete);

        unset($this->session->data['vouchers'][$session_delete]);

        $json = array();

        if (!$this->checkCart()) {
            $json['redirect'] = $this->url->link('checkout/cart', '', true);
        } else {
            $json['total'] = sprintf($this->language->get('text_items'), $this->cart->countProducts() + (isset($this->session->data['vouchers']) ? count($this->session->data['vouchers']) : 0), $this->registry->get('currency')->format($this->model_extension_module_so_onepagecheckout->getTotal(), $this->session->data['currency']));
        }

        echo json_encode($json);
        exit;
    }

    private function checkCart() {
        // Validate cart has products and has stock. 
        if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
            return false;
        }

        // Validate minimum quantity requirements. 
        $products = $this->cart->getProducts();

        foreach ($products as $product) {
            $product_total = 0;

            foreach ($products as $product_2) {
                if ($product_2['product_id'] == $product['product_id']) {
                    $product_total += $product_2['quantity'];
                }
            }

            if ($product['minimum'] > $product_total) {
                return false;
            }
        }

        return true;
    }

    public function confirm() {
        /* exit if page is accessed via get method */
        if ($this->request->server['REQUEST_METHOD'] != 'POST') {
            return;
        }
        $this->load->model('setting/setting');
        $setting_so_onepagecheckout = $this->model_setting_setting->getSetting('so_onepagecheckout');
        $setting_so_onepagecheckout_general = $setting_so_onepagecheckout['so_onepagecheckout_general'];
        $setting_so_onepagecheckout_layout_setting = $setting_so_onepagecheckout['so_onepagecheckout_layout_setting'];

        $order_data = $this->model_extension_module_so_onepagecheckout->getOrder();

        $new_payment_address = null;
        $new_shipping_address = null;

        $register_account = $this->getProperty($this->request->post, 'account', 'register');

        $errors = array();
        $redirect_cart = '';

        if (!$this->checkCart()) {
            $errors['cart'] = '';
            $redirect_cart = $this->url->link('checkout/cart', '', true);
        }

        if ($this->isLoggedIn()) {
            // payment data 
            if ($this->getProperty($this->request->post, 'payment_address') === 'existing') {
                $address_info = $this->model_account_address->getAddress($this->getProperty($this->request->post, 'payment_address_id'));
                $order_data = array_replace($order_data, $this->getAddressData($address_info, '', 'payment_'));
            } else {
                $new_payment_address = $this->getAddressData($this->request->post, 'payment_', 'payment_');
                $order_data = array_replace($order_data, $new_payment_address);
                $errors = array_merge($errors, $this->validateAddressData($new_payment_address, 'payment_'));
            }

            // shipping data 
            if ($this->isShippingRequired()) {
                if ($this->getProperty($this->request->post, 'shipping_address') === 'existing') {
                    $address_info = $this->model_account_address->getAddress($this->getProperty($this->request->post, 'shipping_address_id'));
                    $order_data = array_replace($order_data, $this->getAddressData($address_info, '', 'shipping_'));
                } else {
                    $new_shipping_address = $this->getAddressData($this->request->post, 'shipping_', 'shipping_');
                    $order_data = array_replace($order_data, $new_shipping_address);
                    $errors = array_merge($errors, $this->validateAddressData($new_shipping_address, 'shipping_'));
                }
            }

            // customer data 
            if (!$errors) {
                $customer_info = $this->model_account_customer->getCustomer($this->customer->getId());

                $order_data['customer_id'] = $this->customer->getId();
                $order_data['customer_group_id'] = $customer_info['customer_group_id'];
                $order_data['firstname'] = $customer_info['firstname'];
                $order_data['lastname'] = $customer_info['lastname'];
                $order_data['email'] = $customer_info['email'];
                $order_data['telephone'] = $customer_info['telephone'];
                $order_data['fax'] = $customer_info['fax'];
                $order_data['custom_field'] = version_compare(VERSION, '2.1', '>=') ? json_decode($customer_info['custom_field'], true) : unserialize($customer_info['custom_field']);
            }
        } else {
            // check firstname, lastname 
            $errors = array_merge($errors, $this->validateUserData($this->request->post, $register_account));

            // check customer group id 
            if (isset($this->request->post['customer_group_id']) && is_array($this->config->get('config_customer_group_display')) && in_array($this->request->post['customer_group_id'], $this->config->get('config_customer_group_display'))) {
                $order_data['customer_group_id'] = $this->request->post['customer_group_id'];
            } else {
                $order_data['customer_group_id'] = $this->config->get('config_customer_group_id');
            }

            if (!isset($this->request->post['account'])) {
                $errors['account'] = array_merge($errors, array('Does not account exists! Please check configuration'));
            }

            // check passwords if register 
            if (($register_account == 'register') && isset($this->request->post['account'])) {
                $errors = array_merge($errors, $this->validatePassword($this->request->post));
            }

            // check payment address 
            $new_payment_address = $this->getAddressData($this->request->post, 'payment_', 'payment_');
            $order_data = array_replace($order_data, $new_payment_address);
            $errors = array_merge($errors, $this->validateAddressData($new_payment_address, 'payment_', false));

            // add payment firstname and lastname 
            $order_data['firstname'] = $this->request->post['firstname'];
            $order_data['lastname'] = $this->request->post['lastname'];
            $order_data['email'] = $this->request->post['email'];
            $order_data['telephone'] = $this->request->post['telephone'];
            $order_data['fax'] = $this->request->post['fax'];
            $order_data['custom_field'] = $this->getProperty($this->request->post, 'custom_field', array());
            $order_data['payment_firstname'] = $order_data['firstname'];
            $order_data['payment_lastname'] = $order_data['lastname'];

            // check delivery address 
            if ($this->isShippingRequired()) {
                if (!$this->getProperty($this->request->post, 'shipping_address')) {
                    $new_shipping_address = $this->getAddressData($this->request->post, 'shipping_', 'shipping_');
                    $order_data = array_replace($order_data, $new_shipping_address);
                    $errors = array_merge($errors, $this->validateAddressData($new_shipping_address, 'shipping_'));
                } else {
                    $order_data = array_replace($order_data, $this->getAddressData($order_data, 'payment_', 'shipping_'));
                }
            }
        }

        // payment method 
        if ($payment_method = $this->getProperty($this->session->data, 'payment_methods.' . $this->getProperty($this->request->post, 'payment_method') . '.title')) {
            $order_data['payment_method'] = $payment_method;
            $order_data['payment_code'] = $this->getProperty($this->request->post, 'payment_method');
        } else {
            $errors['payment_method'] = str_replace('&nbsp;', '', strip_tags($this->language->get('error_no_payment')));
        }

        // shipping method 
        if ($this->isShippingRequired()) {
            $shipping = explode('.', $this->getProperty($this->request->post, 'shipping_method'));
            if (is_array($shipping) && count($shipping) > 1) {
                $shipping_method = $this->session->data['shipping_methods'][$shipping[0]]['quote'][$shipping[1]];
                if ($shipping_method) {
                    $order_data['shipping_method'] = $shipping_method['title'];
                    $order_data['shipping_code'] = $this->getProperty($this->request->post, 'shipping_method');
                } else {
                    $order_data['shipping_method'] = 'no shipping method';
                    $errors['shipping_method'] = str_replace('&nbsp;', '', strip_tags($this->language->get('error_no_shipping')));
                }
            } else {
                $order_data['shipping_method'] = 'no shipping method';
                $errors['shipping_method'] = str_replace('&nbsp;', '', strip_tags($this->language->get('error_no_shipping')));
            }
        }

        // order totals 
        $totals = array();
        $taxes = $this->cart->getTaxes();
        $total = 0;

        // Because __call can not keep var references so we put them into an array. 
        $total_data = array(
            'totals' => &$totals,
            'taxes' => &$taxes,
            'total' => &$total
        );

        $this->load->model('setting/extension');
        $results = $this->model_setting_extension->getExtensions('total');

        $sort_order = array();

        foreach ($results as $key => $value) {
            $sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
        }

        array_multisort($sort_order, SORT_ASC, $results);

        foreach ($results as $result) {
            if ($this->config->get('total_' . $result['code'] . '_status')) {
                $this->load->model('extension/total/' . $result['code']);

                $this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
            }
        }

        $sort_order = array();

        foreach ($totals as $key => $value) {
            $sort_order[$key] = $value['sort_order'];
        }

        array_multisort($sort_order, SORT_ASC, $totals);

        $order_data['totals'] = $totals;
        $order_data['total'] = $total;

        // order products 
        $order_data['products'] = array();

        foreach ($this->cart->getProducts() as $product) {
            $option_data = array();

            foreach ($product['option'] as $option) {
                $option_data[] = array(
                    'product_option_id' => $option['product_option_id'],
                    'product_option_value_id' => $option['product_option_value_id'],
                    'option_id' => $option['option_id'],
                    'option_value_id' => $option['option_value_id'],
                    'name' => $option['name'],
                    'value' => $option['value'],
                    'type' => $option['type']
                );
            }

            $order_data['products'][] = array(
                'product_id' => $product['product_id'],
                'name' => $product['name'],
                'model' => $product['model'],
                'option' => $option_data,
                'download' => $product['download'],
                'quantity' => $product['quantity'],
                'subtract' => $product['subtract'],
                'price' => $product['price'],
                'total' => $product['total'],
                'tax' => $this->tax->getTax($product['price'], $product['tax_class_id']),
                'reward' => $product['reward']
            );
        }

        // Gift Voucher 
        $order_data['vouchers'] = array();

        if (!empty($this->session->data['vouchers'])) {
            foreach ($this->session->data['vouchers'] as $voucher) {
                $order_data['vouchers'][] = array(
                    'description' => $voucher['description'],
                    'code' => substr(md5(mt_rand()), 0, 10),
                    'to_name' => $voucher['to_name'],
                    'to_email' => $voucher['to_email'],
                    'from_name' => $voucher['from_name'],
                    'from_email' => $voucher['from_email'],
                    'voucher_theme_id' => $voucher['voucher_theme_id'],
                    'message' => $voucher['message'],
                    'amount' => $voucher['amount']
                );
            }
        }

        // comment + checkboxes 
        $order_data['comment'] = $this->getProperty($this->request->post, 'comment');
        if ($setting_so_onepagecheckout_layout_setting['require_comment_status'] == 1 && empty($order_data['comment'])) {
            $errors['comment'] = $this->language->get('error_comment');
        }

        if (!$this->isLoggedIn() && $this->config->get('config_account_id')) {
            $this->load->model('catalog/information');

            $information_info = $this->model_catalog_information->getInformation($this->config->get('config_account_id'));

            if ($information_info && !isset($this->request->post['privacy'])) {
                $errors['privacy'] = sprintf($this->language->get('error_agree'), $information_info['title']);
            }
        }

        if ($this->config->get('config_checkout_id')) {
            $this->load->model('catalog/information');

            $information_info = $this->model_catalog_information->getInformation($this->config->get('config_checkout_id'));

            if ($information_info && !isset($this->request->post['agree'])) {
                $errors['agree'] = sprintf($this->language->get('error_agree'), $information_info['title']);
            }
        }

        if (isset($errors['agree']) && empty($setting_so_onepagecheckout_layout_setting['show_term'])) {
            unset($errors['agree']);
        }

        if ($this->config->get('config_account_id') == $this->config->get('config_checkout_id')) {
            unset($errors['privacy']);
        }

        $redirect = '';

        // update order 
        $this->model_extension_module_so_onepagecheckout->setOrderData($order_data);

        if ($this->getProperty($this->request->get, 'saveOnly') === 'true') {
            header('Content-Type: application/json');
            echo json_encode(array(
                'order_data' => $order_data
            ));
            exit;
        }

        if (!$errors) {
            if ($this->isLoggedIn()) {
                // save new payment address 
                if ($new_payment_address) {
                    $this->model_account_address->addAddress($this->customer->getId(), $this->getAddressData($new_payment_address, 'payment_'));
                }

                // save new shipping address 
                if ($new_shipping_address && $new_shipping_address !== $new_payment_address) {
                    $this->model_account_address->addAddress($this->customer->getId(), $this->getAddressData($new_shipping_address, 'shipping_'));
                }

                $this->model_extension_module_so_onepagecheckout->updateCustomer();
            } else if ($register_account == 'register') {
                $redirect = $this->registerAccount();
                $sql = "UPDATE `" . DB_PREFIX . "order` SET customer_id = '" . (int) ($this->customer->getId()) . "', customer_group_id = '" . (int) ($this->customer->getGroupId()) . "' WHERE order_id = '" . (int) ($this->session->data['order_id']) . "'";
                $this->db->query($sql);
            } else {
                $this->session->data['guest'] = $this->getAddressData($order_data, 'payment_');
            }
        }

        $this->session->data['so_checkout_account'] = $this->getProperty($this->request->post, 'account');
        $this->session->data['so_checkout_shipping_address'] = $this->getProperty($this->request->post, 'shipping_address', '0');

        // send response 
        header('Content-Type: application/json');
        echo json_encode(array(
            'errors' => $errors ? $errors : null,
            'account_status' => $this->isLoggedIn() ? 1 : 0,
            'redirect' => $redirect,
            'redirect_cart' => $redirect_cart,
            'order_data' => $order_data
        ));
        exit;
    }

    private function validateAddressData($data, $key, $name = true) {
        $errors = array();

        if ($name) {
            // firstname 
            if ((utf8_strlen(trim($data[$key . 'firstname'])) < 1) || (utf8_strlen(trim($data[$key . 'firstname'])) > 32)) {
                $errors[$key . 'firstname'] = $this->language->get('error_firstname');
            }

            // lastname 
            if ((utf8_strlen(trim($data[$key . 'lastname'])) < 1) || (utf8_strlen(trim($data[$key . 'lastname'])) > 32)) {
                $errors[$key . 'lastname'] = $this->language->get('error_lastname');
            }
        }

        if ((utf8_strlen(trim($data[$key . 'address_1'])) < 3) || (utf8_strlen(trim($data[$key . 'address_1'])) > 128)) {
            $errors[$key . 'address_1'] = $this->language->get('error_address_1');
        }

        if ((utf8_strlen($data[$key . 'city']) < 2) || (utf8_strlen($data[$key . 'city']) > 32)) {
            $errors[$key . 'city'] = $this->language->get('error_city');
        }

        $country_info = $this->model_localisation_country->getCountry($data[$key . 'country_id']);

        if ($country_info && $country_info['postcode_required'] && (utf8_strlen(trim($data[$key . 'postcode'])) < 2 || utf8_strlen(trim($data[$key . 'postcode'])) > 10)) {
            $errors[$key . 'postcode'] = $this->language->get('error_postcode');
        }

        if ($data[$key . 'country_id'] == '') {
            $errors[$key . 'country'] = $this->language->get('error_country');
        }

        if (!isset($data[$key . 'zone_id']) || $data[$key . 'zone_id'] == '' || !is_numeric($data[$key . 'zone_id'])) {
            $errors[$key . 'zone'] = $this->language->get('error_zone');
        }

        // Custom field validation 
        $custom_fields = $this->model_extension_module_so_onepagecheckout->getCustomFields();
        foreach ($custom_fields as $custom_field) {
            if (($custom_field['location'] == 'address') && $custom_field['required'] && empty($data[$key . 'custom_field'][$custom_field['custom_field_id']])) {
                $errors[$key . 'custom_field' . $custom_field['custom_field_id']] = sprintf($this->language->get('error_custom_field'), $custom_field['name']);
            }
        }

        return $errors;
    }

    private function validateUserData($data, $register) {
        $errors = array();

        // firstname 
        if ((utf8_strlen(trim($data['firstname'])) < 1) || (utf8_strlen(trim($data['firstname'])) > 32)) {
            $errors['firstname'] = $this->language->get('error_firstname');
        }

        // lastname 
        if ((utf8_strlen(trim($data['lastname'])) < 1) || (utf8_strlen(trim($data['lastname'])) > 32)) {
            $errors['lastname'] = $this->language->get('error_lastname');
        }

        // email 
        if ((utf8_strlen($data['email']) > 96) || !preg_match('/^[^\@]+@.*.[a-z]{2,15}$/i', $data['email'])) {
            $errors['email'] = $this->language->get('error_email');
        } else if ($register && $this->model_account_customer->getTotalCustomersByEmail($data['email'])) {
            $errors['email'] = $this->language->get('error_exists');
        }

        // telephone 
        if ((utf8_strlen($data['telephone']) < 3) || (utf8_strlen($data['telephone']) > 32)) {
            $errors['telephone'] = $this->language->get('error_telephone');
        }

        // Custom field validation 
        $custom_fields = $this->model_extension_module_so_onepagecheckout->getCustomFields();

        foreach ($custom_fields as $custom_field) {
            if (($custom_field['location'] == 'account') && $custom_field['required'] && empty($data['custom_field'][$custom_field['custom_field_id']])) {
                $errors['custom_field' . $custom_field['custom_field_id']] = sprintf($this->language->get('error_custom_field'), $custom_field['name']);
            }
        }

        return $errors;
    }

    private function validatePassword($data) {
        $errors = array();

        if ((utf8_strlen($data['password']) < 4) || (utf8_strlen($data['password']) > 20)) {
            $errors['password'] = $this->language->get('error_password');
        }

        if ($data['confirm'] != $data['password']) {
            $errors['confirm'] = $this->language->get('error_confirm');
        }

        return $errors;
    }

    private function getAddressData($array, $i_address = '', $prefix = '') {
        $keys = array(
            'address_1',
            'address_2',
            'address_id',
            'address_format',
            'city',
            'company',
            'company_id',
            'country',
            'country_id',
            'firstname',
            'lastname',
            'method',
            'postcode',
            'tax_id',
            'zone',
            'zone_id'
        );

        $result = array();

        foreach ($keys as $k) {
            $result[$prefix . $k] = $this->getProperty($array, $i_address . $k, '');
        }

        if ($result[$prefix . 'country_id']) {
            $country_info = $this->model_localisation_country->getCountry($result[$prefix . 'country_id']);
            if ($country_info) {
                if (!$result[$prefix . 'country']) {
                    $result[$prefix . 'country'] = $country_info['name'];
                }
                $result[$prefix . 'address_format'] = $country_info['address_format'];
            }
        }

        if (!$result[$prefix . 'zone'] && $result[$prefix . 'zone_id']) {
            $zone_info = $this->model_localisation_zone->getZone($result[$prefix . 'zone_id']);
            if ($zone_info) {
                $result[$prefix . 'zone'] = $zone_info['name'];
            }
        }

        $result[$prefix . 'custom_field'] = $this->getProperty($array, $i_address . 'custom_field', array());

        return $result;
    }

    private function registerAccount() {
        $redirect = '';

        $data = $this->getAddressData($this->request->post, 'payment_');

        $data = array_merge($data, array(
            'firstname' => $this->getProperty($this->request->post, 'firstname'),
            'lastname' => $this->getProperty($this->request->post, 'lastname'),
            'customer_group_id' => $this->getProperty($this->request->post, 'customer_group_id', $this->config->get('config_customer_group_id')),
            'custom_field' => array(
                'account' => $this->getProperty($this->request->post, 'custom_field'),
                'address' => $this->getProperty($this->request->post, 'payment_custom_field'),
            ),
            'email' => $this->getProperty($this->request->post, 'email'),
            'telephone' => $this->getProperty($this->request->post, 'telephone'),
            'fax' => $this->getProperty($this->request->post, 'fax'),
            'password' => $this->getProperty($this->request->post, 'password'),
            'newsletter' => $this->getProperty($this->request->post, 'newsletter')
        ));

        $customer_id = $this->model_account_customer->addCustomer($data);

        // Clear any previous login attempts for unregistered accounts. 
        $this->model_account_customer->deleteLoginAttempts($data['email']);

        $this->session->data['account'] = 'register';

        $customer_group_info = $this->model_account_customer_group->getCustomerGroup($this->getProperty($this->request->post, 'customer_group_id', $this->config->get('config_customer_group_id')));

        if ($customer_group_info && !$customer_group_info['approval']) {
            $this->customer->login($data['email'], $data['password']);

            if ($this->getProperty($this->request->post, 'shipping_address') != '1') {
                $this->model_account_address->addAddress($customer_id, $this->getAddressData($this->request->post, 'shipping_'));
            }

            // Add to activity log 
            $activity_data = array(
                'customer_id' => $customer_id,
                'name' => $data['firstname'] . ' ' . $data['lastname']
            );

            $this->model_account_activity->addActivity('register', $activity_data);
        } else {
            $redirect = $this->url->link('account/success');
        }

        return $redirect;
    }

    public function login() {
        $json = array();

        if ($this->customer->isLogged()) {
            $json['redirect'] = $this->url->link('checkout/checkout', '', true);
        }

        if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
            $json['redirect'] = $this->url->link('checkout/cart', '', true);
        }

        if (!$json) {
            $this->load->model('account/customer');

            // Check how many login attempts have been made. 
            $login_info = $this->model_account_customer->getLoginAttempts($this->request->post['email']);

            if ($login_info && ($login_info['total'] >= $this->config->get('config_login_attempts')) && strtotime('-1 hour') < strtotime($login_info['date_modified'])) {
                $json['error']['warning'] = $this->language->get('error_attempts');
            }

            // Check if customer has been approved. 
            $customer_info = $this->model_account_customer->getCustomerByEmail($this->request->post['email']);

            if ($customer_info && !$customer_info['status']) {
                $json['error']['warning'] = $this->language->get('error_approved');
            }

            if (!isset($json['error'])) {
                if (!$this->customer->login($this->request->post['email'], $this->request->post['password'])) {
                    $json['error']['warning'] = $this->language->get('error_login');

                    $this->model_account_customer->addLoginAttempt($this->request->post['email']);
                } else {
                    $this->model_account_customer->deleteLoginAttempts($this->request->post['email']);
                }
            }
        }

        if (!$json) {
            unset($this->session->data['guest']);

            $this->load->model('account/address');

            $address_info = $this->model_account_address->getAddress($this->customer->getAddressId());

            if ($this->config->get('config_tax_customer') == 'payment') {
                $this->session->data['payment_address'] = $address_info;
            }

            if ($this->config->get('config_tax_customer') == 'shipping') {
                $this->session->data['shipping_address'] = $address_info;
            }

            $this->model_extension_module_so_onepagecheckout->setAddress('shipping', $address_info);
            $this->model_extension_module_so_onepagecheckout->setAddress('payment', $address_info);
            $this->model_extension_module_so_onepagecheckout->save();

            $json['redirect'] = $this->url->link('checkout/checkout', '', true);

            // Add to activity log 
            $this->load->model('account/activity');

            $activity_data = array(
                'customer_id' => $this->customer->getId(),
                'name' => $this->customer->getFirstName() . ' ' . $this->customer->getLastName()
            );

            $this->model_account_activity->addActivity('login', $activity_data);
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function index() {
        // Validate cart has products and has stock.
        if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
            $this->response->redirect($this->url->link('checkout/cart'));
        }

        // Validate minimum quantity requirements.
        $products = $this->cart->getProducts();

        foreach ($products as $product) {
            $product_total = 0;

            foreach ($products as $product_2) {
                if ($product_2['product_id'] == $product['product_id']) {
                    $product_total += $product_2['quantity'];
                }
            }

            if ($product['minimum'] > $product_total) {
                $this->response->redirect($this->url->link('checkout/cart'));
            }
        }

        $this->load->language('checkout/checkout');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment/moment.min.js');
        $this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment/moment-with-locales.min.js');
        $this->document->addScript('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.js');
        $this->document->addStyle('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.css');

        // Required by klarna
        if ($this->config->get('payment_klarna_account') || $this->config->get('payment_klarna_invoice')) {
            $this->document->addScript('http://cdn.klarna.com/public/kitt/toc/v1.0/js/klarna.terms.min.js');
        }


        $this->load->model('setting/setting');
        $setting_so_onepagecheckout = $this->model_setting_setting->getSetting('so_onepagecheckout');
        if (isset($setting_so_onepagecheckout) && count($setting_so_onepagecheckout) > 0) {
            $setting_so_onepagecheckout_general = $setting_so_onepagecheckout['so_onepagecheckout_general'];
            if (isset($setting_so_onepagecheckout_general['so_onepagecheckout_enabled']) && $setting_so_onepagecheckout_general['so_onepagecheckout_enabled']) {
                $this->document->addStyle('catalog/view/javascript/so_onepagecheckout/css/so_onepagecheckout.css');
                $this->load->model('localisation/country');
                $data['countries'] = $this->model_localisation_country->getCountries();
            }
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_cart'),
            'href' => $this->url->link('checkout/cart')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('checkout/checkout', '', true)
        );

        $data['text_checkout_option'] = sprintf($this->language->get('text_checkout_option'), 1);
        $data['text_checkout_account'] = sprintf($this->language->get('text_checkout_account'), 2);
        $data['text_checkout_payment_address'] = sprintf($this->language->get('text_checkout_payment_address'), 2);
        $data['text_checkout_shipping_address'] = sprintf($this->language->get('text_checkout_shipping_address'), 3);
        $data['text_checkout_shipping_method'] = sprintf($this->language->get('text_checkout_shipping_method'), 4);

        if ($this->cart->hasShipping()) {
            $data['text_checkout_payment_method'] = sprintf($this->language->get('text_checkout_payment_method'), 5);
            $data['text_checkout_confirm'] = sprintf($this->language->get('text_checkout_confirm'), 6);
        } else {
            $data['text_checkout_payment_method'] = sprintf($this->language->get('text_checkout_payment_method'), 3);
            $data['text_checkout_confirm'] = sprintf($this->language->get('text_checkout_confirm'), 4);
        }

        if (isset($this->session->data['error'])) {
            $data['error_warning'] = $this->session->data['error'];
            unset($this->session->data['error']);
        } else {
            $data['error_warning'] = '';
        }

        $data['logged'] = $this->customer->isLogged();

        if (isset($this->session->data['account'])) {
            $data['account'] = $this->session->data['account'];
        } else {
            $data['account'] = '';
        }

        $data['shipping_required'] = $this->cart->hasShipping();

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');


        $this->load->model('setting/setting');
        $setting_so_onepagecheckout = $this->model_setting_setting->getSetting('so_onepagecheckout');
        if (isset($setting_so_onepagecheckout) && count($setting_so_onepagecheckout) > 0) {
            $setting_so_onepagecheckout_general = $setting_so_onepagecheckout['so_onepagecheckout_general'];
            $setting_so_onepagecheckout_layout_setting = $setting_so_onepagecheckout['so_onepagecheckout_layout_setting'];

            if (isset($setting_so_onepagecheckout_general['so_onepagecheckout_enabled']) && $setting_so_onepagecheckout_general['so_onepagecheckout_enabled']) {
                if (isset($setting_so_onepagecheckout_layout_setting['so_onepagecheckout_account_open']) && !empty($setting_so_onepagecheckout_layout_setting['so_onepagecheckout_account_open'])) {
                    $data['default_auth'] = $setting_so_onepagecheckout_layout_setting['so_onepagecheckout_account_open'];
                } else {
                    $data['default_auth'] = $this->getProperty($this->session->data, 'so_checkout_account', 'register');
                }

                $data['setting_so_onepagecheckout'] = $setting_so_onepagecheckout;
                $data['setting_so_onepagecheckout_general'] = $setting_so_onepagecheckout_general;
                $data['setting_so_onepagecheckout_layout_setting'] = $setting_so_onepagecheckout_layout_setting;

                $data['text_register'] = $this->language->get('text_register');
                $data['text_guest'] = $this->language->get('text_guest');
                $data['entry_email'] = $this->language->get('entry_email');
                $data['entry_password'] = $this->language->get('entry_password');
                $data['text_forgotten'] = $this->language->get('text_forgotten');
                $data['text_loading'] = $this->language->get('text_loading');
                $data['button_login'] = $this->language->get('button_login');
                $data['text_i_am_returning_customer'] = $this->language->get('text_i_am_returning_customer');
                $data['text_returning_customer'] = $this->language->get('text_returning_customer');
                $data['text_checkout_create_account_login'] = $this->language->get('text_checkout_create_account_login');

                $data['text_your_details'] = $this->language->get('text_your_details');
                $data['entry_customer_group'] = $this->language->get('entry_customer_group');
                $data['entry_firstname'] = $this->language->get('entry_firstname');
                $data['entry_lastname'] = $this->language->get('entry_lastname');
                $data['entry_telephone'] = $this->language->get('entry_telephone');
                $data['entry_fax'] = $this->language->get('entry_fax');
                $data['text_your_password'] = $this->language->get('text_your_password');
                $data['entry_confirm'] = $this->language->get('entry_confirm');
                $data['text_your_address'] = $this->language->get('text_your_address');
                $data['entry_shipping'] = $this->language->get('entry_shipping');
                $data['text_confirm_order'] = $this->language->get('text_confirm_order');

                // address data 
                if ($this->isLoggedIn()) {
                    $data['is_logged_in'] = true;
                    $data['payment_address'] = $this->renderAddressForm('payment');
                    $data['shipping_address'] = $this->renderAddressForm('shipping');
                    $data['register_form'] = '';
                } else {
                    $data['is_logged_in'] = false;
                    $data['allow_guest_checkout'] = $this->allowGuestCheckout();
                    $data['register_form'] = $this->renderRegisterForm();
                }

                // shipping 
                if ($this->isShippingRequired() && $setting_so_onepagecheckout_layout_setting['delivery_method_status']) {
                    $data['is_shipping_required'] = true;
                    $data['shipping_methods'] = $this->shipping(true);
                } else {
                    $data['is_shipping_required'] = false;
                }

                // payment 
                $data['payment_methods'] = $this->payment(true);

                // coupon + voucher 
                $data['coupon_voucher_reward'] = $this->renderCouponVoucherReward();

                // cart 
                if (isset($setting_so_onepagecheckout_layout_setting['shopping_cart_status']) && $setting_so_onepagecheckout_layout_setting['shopping_cart_status']) {
                    $data['cart'] = $this->cart(true);
                } else {
                    $data['cart'] = '';
                }

                // checkboxes 
                if (!$this->isLoggedIn() && $this->config->get('config_account_id')) {
                    $this->load->model('catalog/information');

                    $information_info = $this->model_catalog_information->getInformation($this->config->get('config_account_id'));

                    if ($information_info) {
                        $data['text_privacy'] = sprintf($this->language->get('text_agree'), $this->url->link('information/information/agree', 'information_id=' . $this->config->get('config_account_id'), true), $information_info['title'], $information_info['title']);
                    } else {
                        $data['text_privacy'] = '';
                    }
                } else {
                    $data['text_privacy'] = '';
                }

                if ($this->config->get('config_checkout_id')) {
                    $this->load->model('catalog/information');

                    $information_info = $this->model_catalog_information->getInformation($this->config->get('config_checkout_id'));

                    if ($information_info) {
                        $data['text_agree'] = sprintf($this->language->get('text_agree'), $this->url->link('information/information/agree', 'information_id=' . $this->config->get('config_checkout_id'), true), $information_info['title'], $information_info['title']);
                    } else {
                        $data['text_agree'] = '';
                    }
                } else {
                    $data['text_agree'] = '';
                }

                if ($data['text_privacy'] === $data['text_agree']) {
                    $data['text_privacy'] = '';
                }

                $data['text_comments'] = $this->language->get('text_comments');

                if ($this->isLoggedIn()) {
                    $data['entry_newsletter'] = false;
                } else {
                    $data['entry_newsletter'] = sprintf($this->language->get('entry_newsletter'), $this->config->get('config_name'));
                }

                $data['comment'] = $this->model_extension_module_so_onepagecheckout->getComment();

                if (isset($this->session->data['error'])) {
                    $data['error_warning'] = $this->session->data['error'];
                    unset($this->session->data['error']);
                } else {
                    $data['error_warning'] = '';
                }

                $data['customer_groups'] = array();
                if (is_array($this->config->get('config_customer_group_display'))) {
                    $this->load->model('account/customer_group');

                    $customer_groups = $this->model_account_customer_group->getCustomerGroups();

                    foreach ($customer_groups as $customer_group) {
                        if (in_array($customer_group['customer_group_id'], $this->config->get('config_customer_group_display'))) {
                            $data['customer_groups'][] = $customer_group;
                        }
                    }
                }
                if (isset($this->request->post['customer_group_id'])) {
                    $data['customer_group_id'] = $this->request->post['customer_group_id'];
                } else {
                    $data['customer_group_id'] = $this->config->get('config_customer_group_id');
                }



                //Get cart 
                $this->load->model('tool/image');
                $this->load->model('tool/upload');

                $data['products'] = array();
                $products = $this->cart->getProducts();
                foreach ($products as $product) {
                    $product_total = 0;

                    foreach ($products as $product_2) {
                        if ($product_2['product_id'] == $product['product_id']) {
                            $product_total += $product_2['quantity'];
                        }
                    }

                    if ($product['minimum'] > $product_total) {
                        $data['error_warning'] = sprintf($this->language->get('error_minimum'), $product['name'], $product['minimum']);
                    }

                    if ($product['image']) {
                        $image = $this->model_tool_image->resize($product['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_cart_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_cart_height'));
                    } else {
                        $image = '';
                    }

                    $option_data = array();

                    foreach ($product['option'] as $option) {
                        if ($option['type'] != 'file') {
                            $value = $option['value'];
                        } else {
                            $upload_info = $this->model_tool_upload->getUploadByCode($option['value']);

                            if ($upload_info) {
                                $value = $upload_info['name'];
                            } else {
                                $value = '';
                            }
                        }

                        $option_data[] = array(
                            'name' => $option['name'],
                            'value' => (utf8_strlen($value) > 20 ? utf8_substr($value, 0, 20) . '..' : $value)
                        );
                    }

                    // Display prices 
                    if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
                        $unit_price = $this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax'));

                        $price = $this->currency->format($unit_price, $this->session->data['currency']);
                        $total = $this->currency->format($unit_price * $product['quantity'], $this->session->data['currency']);
                    } else {
                        $price = false;
                        $total = false;
                    }

                    $recurring = '';

                    if ($product['recurring']) {
                        $frequencies = array(
                            'day' => $this->language->get('text_day'),
                            'week' => $this->language->get('text_week'),
                            'semi_month' => $this->language->get('text_semi_month'),
                            'month' => $this->language->get('text_month'),
                            'year' => $this->language->get('text_year'),
                        );

                        if ($product['recurring']['trial']) {
                            $recurring = sprintf($this->language->get('text_trial_description'), $this->currency->format($this->tax->calculate($product['recurring']['trial_price'] * $product['quantity'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']), $product['recurring']['trial_cycle'], $frequencies[$product['recurring']['trial_frequency']], $product['recurring']['trial_duration']) . ' ';
                        }

                        if ($product['recurring']['duration']) {
                            $recurring .= sprintf($this->language->get('text_payment_description'), $this->currency->format($this->tax->calculate($product['recurring']['price'] * $product['quantity'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']), $product['recurring']['cycle'], $frequencies[$product['recurring']['frequency']], $product['recurring']['duration']);
                        } else {
                            $recurring .= sprintf($this->language->get('text_payment_cancel'), $this->currency->format($this->tax->calculate($product['recurring']['price'] * $product['quantity'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']), $product['recurring']['cycle'], $frequencies[$product['recurring']['frequency']], $product['recurring']['duration']);
                        }
                    }

                    $data['products'][] = array(
                        'cart_id' => $product['cart_id'],
                        'thumb' => $image,
                        'name' => $product['name'],
                        'model' => $product['model'],
                        'option' => $option_data,
                        'recurring' => $recurring,
                        'quantity' => $product['quantity'],
                        'stock' => $product['stock'] ? true : !(!$this->config->get('config_stock_checkout') || $this->config->get('config_stock_warning')),
                        'reward' => ($product['reward'] ? sprintf($this->language->get('text_points'), $product['reward']) : ''),
                        'price' => $price,
                        'total' => $total,
                        'href' => $this->url->link('product/product', 'product_id=' . $product['product_id'])
                    );
                }

                // Gift Voucher 
                $data['vouchers'] = array();

                if (!empty($this->session->data['vouchers'])) {
                    foreach ($this->session->data['vouchers'] as $key => $voucher) {
                        $data['vouchers'][] = array(
                            'key' => $key,
                            'description' => $voucher['description'],
                            'amount' => $this->currency->format($voucher['amount'], $this->session->data['currency']),
                            'remove' => $this->url->link('checkout/cart', 'remove=' . $key)
                        );
                    }
                }

                // Totals 
                $this->load->model('setting/extension');

                $totals = array();
                $taxes = $this->cart->getTaxes();
                $total = 0;

                // Because __call can not keep var references so we put them into an array. 
                $total_data = array(
                    'totals' => &$totals,
                    'taxes' => &$taxes,
                    'total' => &$total
                );

                // Display prices 
                if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
                    $sort_order = array();

                    $results = $this->model_setting_extension->getExtensions('total');

                    foreach ($results as $key => $value) {
                        $sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
                    }

                    array_multisort($sort_order, SORT_ASC, $results);

                    foreach ($results as $result) {
                        if ($this->config->get('total_' . $result['code'] . '_status')) {
                            $this->load->model('extension/total/' . $result['code']);

                            // We have to put the totals in an array so that they pass by reference. 
                            $this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
                        }
                    }

                    $sort_order = array();

                    foreach ($totals as $key => $value) {
                        $sort_order[$key] = $value['sort_order'];
                    }

                    array_multisort($sort_order, SORT_ASC, $totals);
                }

                $data['totals'] = array();

                foreach ($totals as $total) {
                    $data['totals'][] = array(
                        'title' => $total['title'],
                        'text' => $this->currency->format($total['value'], $this->session->data['currency'])
                    );
                }
            }
        }
        print_r($data);
        die;

        if (isset($setting_so_onepagecheckout_general['so_onepagecheckout_enabled']) && $setting_so_onepagecheckout_general['so_onepagecheckout_enabled']) {
            $data['forgotten'] = $this->url->link('account/forgotten', '', true);
            $this->model_extension_module_so_onepagecheckout->save();
            if ($setting_so_onepagecheckout_general['so_onepagecheckout_layout'] == 1)
                $this->response->setOutput($this->load->view('extension/module/so_onepagecheckout/default', $data));
            else if ($setting_so_onepagecheckout_general['so_onepagecheckout_layout'] == 2)
                $this->response->setOutput($this->load->view('extension/module/so_onepagecheckout/default2', $data));
        } else {
            $this->response->setOutput($this->load->view('checkout/checkout', $data));
        }
    }

    public function country() {
        $json = array();

        $this->load->model('localisation/country');

        $country_info = $this->model_localisation_country->getCountry($this->request->get['country_id']);

        if ($country_info) {
            $this->load->model('localisation/zone');

            $json = array(
                'country_id' => $country_info['country_id'],
                'name' => $country_info['name'],
                'iso_code_2' => $country_info['iso_code_2'],
                'iso_code_3' => $country_info['iso_code_3'],
                'address_format' => $country_info['address_format'],
                'postcode_required' => $country_info['postcode_required'],
                'zone' => $this->model_localisation_zone->getZonesByCountryId($this->request->get['country_id']),
                'status' => $country_info['status']
            );
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function customfield() {
        $json = array();

        $this->load->model('account/custom_field');

        // Customer Group
        if (isset($this->request->get['customer_group_id']) && is_array($this->config->get('config_customer_group_display')) && in_array($this->request->get['customer_group_id'], $this->config->get('config_customer_group_display'))) {
            $customer_group_id = $this->request->get['customer_group_id'];
        } else {
            $customer_group_id = $this->config->get('config_customer_group_id');
        }

        $custom_fields = $this->model_account_custom_field->getCustomFields($customer_group_id);

        foreach ($custom_fields as $custom_field) {
            $json[] = array(
                'custom_field_id' => $custom_field['custom_field_id'],
                'required' => $custom_field['required']
            );
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

}
