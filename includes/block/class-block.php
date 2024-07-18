<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class My_Custom_Gateway_Blocks extends AbstractPaymentMethodType
{

    private $gateway;
    protected $name = 'my_custom_gateway';// your payment gateway name

    /**
     * Summary of initialize
     * @return void
     */
    public function initialize()
    {
        $this->settings = get_option('woocommerce_my_custom_gateway_settings', []);
        $this->gateway = new My_Custom_Gateway();
    }

    /**
     * Summary of is_active
     * @return mixed
     */
    public function is_active()
    {
        return $this->gateway->is_available();
    }

    /**
     * Summary of get_payment_method_script_handles
     * @return string[]
     */
    public function get_payment_method_script_handles()
    {

        wp_register_script(
            'my_custom_gateway-blocks-integration',
            plugin_dir_url(__FILE__) . '/js/checkout.js',
            [
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n',
            ],
            null,
            true
        );
        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations('my_custom_gateway-blocks-integration');

        }
        return ['my_custom_gateway-blocks-integration'];
    }

    /**
     * Summary of get_payment_method_data
     * @return array
     */
    public function get_payment_method_data()
    {
        return [
            'title' => $this->gateway->title,
            'description' => $this->gateway->description,
            'logo_url' => $this->gateway->logo_url,
        ];
    }

}
