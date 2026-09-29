<?php
/**
 * Plugin Name: Paytm WooCommerce Payment Gateway
 * Plugin URI: https://github.com/Paytm/
 * Description: This plugin allow you to accept payments using Paytm. This plugin will add a Paytm Payment option on WooCommerce checkout page, when user choses Paytm as Payment Method, he will redirected to Paytm website to complete his transaction and on completion his payment, paytm will send that user back to your website along with transactions details. This plugin uses server-to-server verification to add additional security layer for validating transactions. Admin can also see payment status for orders by navigating to WooCommerce > Orders from menu in admin.
 * Version: 2.8.9
 * Author: Paytm
 * Author URI: https://www.paytmpayments.com/payment-gateway
 * Tags: Paytm, Paytm Payments, PayWithPaytm, Paytm WooCommerce, Paytm Plugin, Paytm Payment Gateway
 * Requires at least: 4.0.1
 * Tested up to: 7.1
 * Requires PHP: 7.4
 * Text Domain: Paytm Payments
 * WC requires at least: 2.0.0
 * WC tested up to: 11.1.2
 */



/**
 * Add the Gateway to WooCommerce
 **/
if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}


use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;


require_once __DIR__.'/includes/PaytmHelper.php';
require_once __DIR__.'/includes/PaytmChecksum.php';


add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'product_block_editor', __FILE__, true );
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
} );

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'woocommerce_paytm_add_action_links');

function woocommerce_paytm_add_action_links( $links ) 
{
    $settting_url = array(
     '<a href="' . esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=WC_paytm')) . '"><b>Settings</b></a>',
     '<a href="' . esc_url(PaytmConstants::PLUGIN_DOC_URL) . '" target="_blank"><b>Docs</b></a>',
    );
     return array_merge($settting_url, $links);
}

/**
 * Checkout Block code Start
 */
add_action( 'woocommerce_blocks_loaded', 'paytm_register_order_approval_payment_method_type' );

function paytm_register_order_approval_payment_method_type() {
    // Check if the required class exists
    if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
        return;
    }

    // Paytm custom Blocks Checkout class
    require_once plugin_dir_path(__FILE__) . 'class-block.php';
    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
            $payment_method_registry->register( new WC_Paytm_Blocks );
        }
    );
}
/* ************************************************ */

/* Create table 'paytm_order_data' after install paytm plugin */
if (function_exists('register_activation_hook'))
register_activation_hook(__FILE__, 'install_paytm_plugin');
/* Drop table 'paytm_order_data' after uninstall paytm plugin */
if (function_exists('register_deactivation_hook') )
register_deactivation_hook(__FILE__, 'uninstall_paytm_plugin');


function install_paytm_plugin()
{
    global $wpdb;

    $table_name      = $wpdb->prefix . 'paytm_order_data';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$table_name} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		order_id bigint(20) unsigned NOT NULL,
		paytm_order_id varchar(255) NOT NULL,
		transaction_id varchar(255) NOT NULL DEFAULT '',
		status tinyint(1) NOT NULL DEFAULT 0,
		paytm_response longtext NULL,
		date_added datetime NOT NULL,
		date_modified datetime NOT NULL,
		PRIMARY KEY  (id),
		KEY order_id (order_id),
		KEY paytm_order_id (paytm_order_id)
	) {$charset_collate};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

function uninstall_paytm_plugin()
{
   /*  global $wpdb;
    $table_name = $wpdb->prefix . 'paytm_order_data';
    $query = "SELECT * FROM $table_name";
    $results = $wpdb->get_results($query);
    if(count($results) <= 0 ){
        $sql = "DROP TABLE IF EXISTS $table_name";
        $wpdb->query($sql);
    }
    delete_option('woocommerce_paytm_settings'); */
}
function paytmWoopayment_enqueue_style() 
{
    wp_enqueue_style('paytmWoopayment', plugin_dir_url(__FILE__) . 'assets/'.PaytmConstants::PLUGIN_VERSION_FOLDER.'/css/paytm-payments.css', array(), time(), '');
    wp_enqueue_script('paytm-script', plugin_dir_url(__FILE__) . 'assets/'.PaytmConstants::PLUGIN_VERSION_FOLDER.'/js/paytm-payments.js', array('jquery'), time(), true);
}

function paytmWoopayment_js_css(){
    if ( class_exists( 'WooCommerce' ) ) {
        if( is_cart() || is_checkout() ) { 
            add_action('wp_head', 'paytmWoopayment_enqueue_style');
        }
    }
}

add_action( 'wp_enqueue_scripts', 'paytmWoopayment_js_css' );

if (PaytmConstants::SAVE_PAYTM_RESPONSE) {
  
    // Add a paytm payments box only for shop_order post type (order edit pages)
    add_action('add_meta_boxes', 'add_paytm_payment_block');

    //Function changes for woocommerce HPOS features
    function add_paytm_payment_block()
    {

        global $wpdb;
        $settings = get_option("woocommerce_paytm_settings");
        $post_id1 = sanitize_text_field(isset($_GET['post']) ? $_GET['post'] : '');
        $post_id = preg_replace('/[^a-zA-Z0-9]/', '', $post_id1);


        if ($post_id == '' && get_option("woocommerce_custom_orders_table_enabled") == 'yes') {
            $post_id = isset($_GET['id']) ? $_GET['id'] : '';
        }

        if(! $post_id ) return; // Exit
        $screen = wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
        ? wc_get_page_screen_id( 'shop-order' )
        : 'shop_order';
        $results = getPaytmOrderData($post_id);

        // paytm enabled and order is exists with paym_order_data
        if ($settings['enabled'] == 'yes' && !empty($results)) {
            add_meta_box('_paytm_response_table', __('Paytm Payments'), '_paytm_response_table', $screen, 'normal', 'default', array('results' => $results
                )
            );
        }  
    }

    function _paytm_response_table($post = array(), $data = array())
    {
        $results = isset($data['args']['results']) ? $data['args']['results'] : array();

        echo '<div id="paytm_payment_area"><div class="message"></div>';
        echo '<div class="btn-area">';
        echo '<img class="paytm-img-loader" src="' . esc_url(admin_url('images/loading.gif')) . '" alt="" />';
        echo '<button type="button" id="button-paytm-fetch-status" class="button-paytm-fetch-status button">';
        echo esc_html(PaytmConstants::FETCH_BUTTON);
        echo '</button></div>';

        if (!empty($results['paytm_response'])) {
            $paytm_data = json_decode($results['paytm_response'], true);

            if (is_array($paytm_data) && !empty($paytm_data)) {
                echo '<table class="paytm_payment_block" id="paytm_payment_table"><tbody>';

                foreach ($paytm_data as $key => $value) {
                    if ('request' === $key) {
                        continue;
                    }
                    $display = is_scalar($value) ? (string) $value : wp_json_encode($value);

                    echo '<tr>';
                    echo '<td>' . esc_html((string) $key) . '</td>';
                    echo '<td>' . esc_html($display) . '</td>';
                    echo '</tr>';
                }

                echo '</tbody></table>';
                echo '<input type="hidden" id="paytm_order_id" name="paytm_order_id" value="' . esc_attr($results['paytm_order_id']) . '" />';
                echo '<input type="hidden" id="order_data_id" name="order_data_id" value="' . esc_attr($results['id']) . '" />';
                echo '<input type="hidden" id="paytm_woo_nonce" name="paytm_woo_nonce" value="' . esc_attr(wp_create_nonce('paytm_woo_nonce')) . '" />';
            }
        }

        echo '</div>';
    }


    function getPaytmOrderData($order_id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'paytm_order_data';

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM `{$table_name}` WHERE `order_id` = %d ORDER BY `id` DESC LIMIT 1",
                absint($order_id)
            ),
            ARRAY_A
        );
    }

    function get_custom_order($order_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wc_orders';

        $order = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d",
                $order_id
            ),
            ARRAY_A
        );

        if ($order) {
            $order_data = maybe_unserialize($order['order_data']);

            // Additional processing if needed

            return $order_data;
        }

        return null;
    }

    add_action('admin_head', 'woocommerce_paytm_add_css_js');

    function woocommerce_paytm_add_css_js() 
    {
        ?>
    <style>
            #paytm_payment_area .message{float:left;} 
            #paytm_payment_area .btn-area{ float: right;}
            #paytm_payment_area .btn-area .paytm-img-loader{ margin: 6px;float: left; display:none;}
            .paytm_response{padding: 7px 15px;margin-bottom: 20px;border: 1px solid transparent;border-radius: 4px;text-align: center;}
            .paytm_response.error-box{color: #a94442;background-color: #f2dede;border-color: #ebccd1;}
            .paytm_response.success-box{color: #155724;background-color: #d4edda;border-color: #c3e6cb;}
            .paytm_payment_block{table-layout: fixed;width: 100%;}
            .paytm_payment_block td{word-wrap: break-word;}.paytm_highlight{ font-weight: bold;}
            .redColor{color:#f00;}
            .wp-core-ui .button.button-paytm-fetch-status{float: left; line-height: normal; background: #2b9c2b; color: #fff; border-color: #2b9c2b;}
            .wp-core-ui .button.button-paytm-fetch-status:hover{background:#32bd32}
    </style>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            jQuery("#button-paytm-fetch-status").click(function(){
                var paytm_order_id = jQuery("#paytm_order_id").val();
                var order_data_id = jQuery("#order_data_id").val();
                var paytm_woo_nonce = jQuery("#paytm_woo_nonce").val();
                $('.paytm-img-loader').show();

                jQuery.ajax({
                  type:"POST",
                  dataType: 'json',
                  data:{action:"savetxnstatus", paytm_order_id:paytm_order_id, order_data_id:order_data_id,paytm_woo_nonce:paytm_woo_nonce},
                    url: "<?php echo esc_url(admin_url("admin-ajax.php"));?>",
                    success: function(data) {
                        $('.paytm-img-loader').hide();
                        if (data.success == true) {
                            var $tbody = jQuery('#paytm_payment_table tbody');
                            if (!$tbody.length) {
                                jQuery('#paytm_payment_table').html('<tbody></tbody>');
                                $tbody = jQuery('#paytm_payment_table tbody');
                            }
                            $tbody.empty();
                            $.each(data.response, function (index, value) {
                                var $row = jQuery('<tr/>');
                                $row.append(jQuery('<td/>').text(String(index)));
                                $row.append(jQuery('<td/>').text(value == null ? '' : String(value)));
                                $tbody.append($row);
                            });
                            jQuery('#paytm_payment_area div.message')
                                .empty()
                                .append(
                                    jQuery('<div/>', { 'class': 'paytm_response success-box' })
                                        .text(data.message || '')
                                );
                        } else {
                            jQuery('#paytm_payment_area div.message')
                                .empty()
                                .append(
                                    jQuery('<div/>', { 'class': 'paytm_response error-box' })
                                        .text((data && data.message) ? data.message : '')
                                );
                        }
                    }
                });
            });
        });
      </script>
    <?php }

    add_action('wp_ajax_savetxnstatus', 'savetxnstatus');

    function savetxnstatus()
    {

        if (!wp_verify_nonce($_POST['paytm_woo_nonce'], 'paytm_woo_nonce')) die('You are not authorised!');

        $settings = get_option("woocommerce_paytm_settings");
        $json = array("success" => false, "response" => '', 'message' => __(PaytmConstants::RESPONSE_ERROR));

        if (!empty($_POST['paytm_order_id']) && PaytmConstants::SAVE_PAYTM_RESPONSE) {
            $reqParams = array(
                "MID"        => $settings['merchant_id'],
                "ORDERID"    => sanitize_text_field($_POST['paytm_order_id'])
            );

            $reqParams['CHECKSUMHASH'] = PaytmChecksum::generateSignature($reqParams, $settings['merchant_key']);

            $retry = 1;
            do {
                $resParams = PaytmHelper::executecUrl(PaytmHelper::getTransactionStatusURL($settings['environment']), $reqParams);
                $retry++;
            } while(!$resParams['STATUS'] && $retry < PaytmConstants::MAX_RETRY_COUNT);

            if (!empty($resParams['STATUS'])) {
                $response = saveTxnResponse(
                    PaytmHelper::getOrderId(sanitize_text_field(wp_unslash($_POST['paytm_order_id']))),
                    absint($_POST['order_data_id']),
                    $resParams
                );
                if ($response) {
                    $message = __(PaytmConstants::RESPONSE_SUCCESS);
                    $safe_response = array();
                    foreach ($resParams as $key => $value) {
                        if ('request' === $key) {
                            continue;
                        }
                        $safe_key = sanitize_text_field((string) $key);
                        $safe_response[ $safe_key ] = is_scalar($value)
                            ? sanitize_text_field((string) $value)
                            : sanitize_text_field(wp_json_encode($value));
                    }
                    $json = array("success" => true, "response" => $safe_response, 'message' => $message);
                }
            }
        }
        echo wp_json_encode($json);
        die;
    }

    /**
     * Sanitize Paytm response fields before storage/display (XSS hardening).
     *
     * @param array $data Raw Paytm response.
     * @return array
     */
    function paytm_sanitize_response_data($data)
    {
        if (!is_array($data)) {
            return array();
        }

        $safe = array();
        foreach ($data as $key => $value) {
            $safe_key = sanitize_text_field((string) $key);
            if (is_scalar($value) || null === $value) {
                $safe[ $safe_key ] = sanitize_text_field((string) $value);
            } else {
                $safe[ $safe_key ] = sanitize_text_field(wp_json_encode($value));
            }
        }

        return $safe;
    }

    /**
     * Save Paytm response in DB (SQLi-safe via $wpdb->insert / $wpdb->update).
     *
     * @param int        $order_id WC order ID.
     * @param int|false  $id       Existing row ID to update, or false to insert.
     * @param array      $data     Paytm response fields.
     * @return int|false
     */
    function saveTxnResponse($order_id, $id = false, $data = array())
    {
        global $wpdb;

        if (empty($data['STATUS']) || ! is_array($data)) {
            return false;
        }

        $table_name = $wpdb->prefix . 'paytm_order_data';
        $data       = paytm_sanitize_response_data($data);

        $status         = (!empty($data['STATUS']) && $data['STATUS'] === 'TXN_SUCCESS') ? 1 : 0;
        $paytm_order_id = !empty($data['ORDERID']) ? sanitize_text_field($data['ORDERID']) : '';
        $transaction_id = !empty($data['TXNID']) ? sanitize_text_field($data['TXNID']) : '';

        $row = array(
            'order_id'       => absint($order_id),
            'paytm_order_id' => $paytm_order_id,
            'transaction_id' => $transaction_id,
            'status'         => $status,
            'paytm_response' => wp_json_encode($data),
            'date_modified'  => current_time('mysql'),
        );

        if ($id !== false) {
            $wpdb->update(
                $table_name,
                $row,
                array(
                    'id'             => absint($id),
                    'paytm_order_id' => $paytm_order_id,
                ),
                array('%d', '%s', '%s', '%d', '%s', '%s'),
                array('%d', '%s')
            );
            return absint($id);
        }

        $row['date_added'] = current_time('mysql');
        $wpdb->insert(
            $table_name,
            $row,
            array('%d', '%s', '%s', '%d', '%s', '%s', '%s')
        );

        return $wpdb->insert_id ? (int) $wpdb->insert_id : false;
    }
}
    add_action('plugins_loaded', 'woocommerce_paytm_init', 0);

    function woocommerce_paytm_init() {
        // If the WooCommerce payment gateway class is not available nothing will return
       if (!class_exists('WC_Payment_Gateway') ) return;

        // WooCommerce payment gateway class to hook Payment gateway
        require_once(plugin_basename('class.paytm.php'));


        add_filter('woocommerce_payment_gateways', 'woocommerce_add_paytm_gateway' );
        function woocommerce_add_paytm_gateway($methods) 
        {
            $methods[] = 'WC_paytm';
            return $methods;
        }

       /**
         * Localisation
         */
        load_plugin_textdomain('wc-paytm', false, dirname(plugin_basename(__FILE__)) . '/languages');

        if(isset($_GET['paytm_response']) && sanitize_text_field($_GET['paytm_response'])) {
           add_action('the_content', 'paytmResponseMessage');
        }

        add_action('wp_head', 'woocommerce_paytm_front_add_css');

        function woocommerce_paytm_front_add_css() 
        { 
        ?>
        <style>
            .paytm_response{padding:15px; margin-bottom: 20px; border: 1px solid transparent; border-radius: 4px; text-align: center;}
            .paytm_response.error-box{color: #a94442; background-color: #f2dede; border-color: #ebccd1;}
            .paytm_response.success-box{color: #155724; background-color: #d4edda; border-color: #c3e6cb;}
        </style>
        <?php } 

        function paytmResponseMessage($content)
        {
            return '<div class="paytm_response box '.htmlentities(sanitize_text_field($_GET['type'])).'-box">'.htmlentities(urldecode(sanitize_text_field($_GET['paytm_response']))).'</div>'.$content;
        }
    }

add_action('admin_menu', 'paytm_transactions_menu', 99);

function paytm_transactions_menu() {
    add_submenu_page(
        'woocommerce',
        'Paytm Transaction',
        'Paytm Transaction',
        'manage_woocommerce',
        'paytm-transactions',
        'display_paytm_transactions',
        99
    );
}

function display_paytm_transactions() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'paytm_order_data';
    
    // Get date range from URL parameters or set default (last 3 months)
    $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : date('Y-m-d');
    $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : date('Y-m-d', strtotime('-3 months'));
    
    // Get transactions with pagination and date filter
    $page = isset($_GET['pagenum']) ? absint($_GET['pagenum']) : 1;
    $limit = 200; // Items per page
    $offset = ($page - 1) * $limit;
    
    $transactions = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $table_name 
            WHERE DATE(date_added) BETWEEN %s AND %s 
            ORDER BY date_added DESC LIMIT %d OFFSET %d",
            $start_date,
            $end_date,
            $limit,
            $offset
        ),
        ARRAY_A
    );

    $txn_filtered = array();
    foreach($transactions as $tkey=>$tvalue) {
        if(key_exists($tvalue['order_id'],$txn_filtered)) {
            if(json_decode($txn_filtered[$tvalue['order_id']]['paytm_response'],true)['STATUS']=="PENDING" || 
               json_decode($txn_filtered[$tvalue['order_id']]['paytm_response'],true)['STATUS']=="TXN_FAILURE") {
                $txn_filtered[$tvalue['order_id']]=$tvalue;
            }
        } else {
            $txn_filtered[$tvalue['order_id']]=$tvalue;
        }
    }
    
    $total_items = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE DATE(date_added) BETWEEN %s AND %s",
        $start_date,
        $end_date
    ));
    $total_pages = ceil($total_items / $limit);
    
    ?>
    <div class="wrap">
        <h1>Paytm Transactions</h1>

        <!-- Date Range Filter Form -->
        <div class="date-range-filter" style="background: #fff; padding: 15px; margin: 20px 0; border: 1px solid #ddd; border-radius: 4px;">
            <form method="get" action="">
                <input type="hidden" name="page" value="paytm-transactions">
                <div style="display: flex; gap: 20px; align-items: center;">
                    <div>
                        <label for="start_date" style="margin-right: 10px;">From:</label>
                        <input type="date" id="start_date" name="start_date" 
                               value="<?php echo esc_attr($start_date); ?>" 
                               max="<?php echo esc_attr(date('Y-m-d')); ?>">
                    </div>
                    <div>
                        <label for="end_date" style="margin-right: 10px;">To:</label>
                        <input type="date" id="end_date" name="end_date" 
                               value="<?php echo esc_attr($end_date); ?>"
                               max="<?php echo esc_attr(date('Y-m-d')); ?>">
                    </div>
                    <div>
                        <button type="submit" class="button button-primary">Filter</button>
                        <a href="?page=paytm-transactions" class="button">Reset</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Existing table code -->
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>WC Order ID</th>
                    <th>Paytm Order ID</th>
                    <th>Transaction ID</th>
                    <th>Transaction Status</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($txn_filtered as $transaction): ?>
                    <tr>
                        <td>
                            <a href="<?php echo esc_url(admin_url('post.php?post=' . $transaction['order_id'] . '&action=edit')); ?>">
                                <?php echo "<b> # WC Order ".esc_html($transaction['order_id'])."</b>"; ?>
                            </a>
                        </td>
                        <td><?php echo esc_html($transaction['paytm_order_id']); ?></td>
                        <td><?php echo esc_html($transaction['transaction_id']); ?></td>
                        <td><span class="status_span <?php echo esc_html(json_decode($transaction['paytm_response'],true)['STATUS']); ?>" >
                            <?php echo esc_html(json_decode($transaction['paytm_response'],true)['STATUS']); ?>
                        </span></td>
                        <td><?php echo esc_html(date('Y-m-d H:i:s', strtotime($transaction['date_added']))); ?></td>
                        <td>
                            <button type="button" class="button view-details" 
                                    onclick="showTransactionDetails('<?php echo esc_js($transaction['paytm_response']); ?>')">
                                View Details
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination code -->
        <?php if ($total_pages > 1): ?>
        <div class="tablenav">
            <div class="tablenav-pages">
                <?php
                $pagination_args = array(
                    'base' => add_query_arg('pagenum', '%#%'),
                    'format' => '',
                    'prev_text' => __('&laquo;'),
                    'next_text' => __('&raquo;'),
                    'total' => $total_pages,
                    'current' => $page
                );
                
                // Add date range to pagination if set
                if (isset($_GET['start_date']) && isset($_GET['end_date'])) {
                    $pagination_args['add_args'] = array(
                        'start_date' => $start_date,
                        'end_date' => $end_date
                    );
                }
                
                echo esc_html(paginate_links($pagination_args));
                ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Add some CSS for the status spans -->
    <style>
        .status_span {padding: 5px 10px;border-radius: 4px;font-weight: bold;}
        .status_span.TXN_SUCCESS {background-color: #d4edda;color: #155724;}
        .status_span.TXN_FAILURE {background-color: #f8d7da;color: #721c24;}
        .status_span.PENDING {background-color: #fff3cd;color: #856404;}
        .date-range-filter input[type="date"] {padding: 5px;border: 1px solid #ddd;border-radius: 4px;}
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Set max date for date inputs to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('start_date').max = today;
        document.getElementById('end_date').max = today;

        // Validate date range
        document.querySelector('form').addEventListener('submit', function(e) {
            const startDate = new Date(document.getElementById('start_date').value);
            const endDate = new Date(document.getElementById('end_date').value);

            if (startDate > endDate) {
                e.preventDefault();
                alert('Start date cannot be greater than end date');
            }
        });
    });
    </script>

    <!-- Add Modal HTML structure and JavaScript -->
    <div id="transactionModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Transaction Details</h2>
            <table id="transactionDetailsTable">
                <tbody></tbody>
            </table>
        </div>
    </div>

    <style>
        /* Existing styles ... */

        /* Modal styles */
        .modal {display: none;position: fixed;z-index: 999999;left: 0;top: 0;width: 100%;height: 100%;overflow: auto;background-color: rgba(0,0,0,.4);}
        .modal-content {background-color: #fefefe;margin: 5% auto;padding: 20px;border: 1px solid #888;width: 80%;max-width: 800px;border-radius: 5px;}
        .close {color: #aaa;float: right;font-size: 28px;font-weight: 700;cursor: pointer;}
        .close:focus,.close:hover {color: #000;text-decoration: none;cursor: pointer;}
        #transactionDetailsTable {width: 100%;border-collapse: collapse;margin-top: 20px;}
        #transactionDetailsTable td {padding: 8px;border: 1px solid #ddd;}
        #transactionDetailsTable tr td:first-child {font-weight: 700;width: 30%;background-color: #f8f9fa;}
    </style>

    <script>
        // Get modal elements
        const modal = document.getElementById("transactionModal");
        const span = document.getElementsByClassName("close")[0];

        function showTransactionDetails(responseData) {
            try {
                // Parse the JSON response
                const data = JSON.parse(responseData);
                
                // Get the table body
                const tableBody = document.getElementById("transactionDetailsTable").getElementsByTagName("tbody")[0];
                tableBody.innerHTML = ""; // Clear existing content

                // Add each key-value pair to the table
                for (const [key, value] of Object.entries(data)) {
                    if (key !== 'request') { // Skip the request data if present
                        const row = tableBody.insertRow();
                        const cell1 = row.insertCell(0);
                        const cell2 = row.insertCell(1);
                        
                        cell1.textContent = key;
                        cell2.textContent = value;
                    }
                }

                // Show the modal
                modal.style.display = "block";
            } catch (error) {
                console.error("Error parsing transaction details:", error);
                alert("Error displaying transaction details");
            }
        }

        // Close modal when clicking the x button
        span.onclick = function() {
            modal.style.display = "none";
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>

    <?php
}
