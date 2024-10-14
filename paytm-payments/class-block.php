<?php
require_once __DIR__.'/includes/PaytmHelper.php';
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class WC_Paytm_Blocks extends AbstractPaymentMethodType {

     private $gateway;
    protected $name = 'paytm';

    public function initialize() {
        $this->settings = get_option( 'woocommerce_paytm_settings', [] );
       // $this->gateway = new WC_Paytm(); 
    } 

    /*  public function is_active() {
        return $this->gateway->is_available();
    }   */

    public function get_payment_method_script_handles() {

        wp_register_script(
            'paytm-blocks-integration',
            plugin_dir_url(__FILE__) .'assets/'.PaytmConstants::PLUGIN_VERSION_FOLDER.'/js/admin/checkout-block.js',
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
        if( function_exists( 'wp_set_script_translations' ) ) {            
            wp_set_script_translations( 'wc_paytm-blocks-integration');
            
        }
        return [ 'paytm-blocks-integration' ];
    }

    public function get_payment_method_data() {
        return [
            'title' => "Pay With Paytm",
            'description' => $this->settings['description'],
        ];
    } 

}
?>