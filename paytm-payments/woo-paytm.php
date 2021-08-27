<?php
/**
 * Plugin Name: Paytm WooCommerce Payment Gateway
 * Plugin URI: https://github.com/Paytm-Payments/
 * Description: This plugin allow you to accept payments using Paytm. This plugin will add a Paytm Payment option on WooCommerce checkout page, when user choses Paytm as Payment Method, he will redirected to Paytm website to complete his transaction and on completion his payment, paytm will send that user back to your website along with transactions details. This plugin uses server-to-server verification to add additional security layer for validating transactions. Admin can also see payment status for orders by navigating to WooCommerce > Orders from menu in admin.
 * Version: 2.6.2
 * Author: Paytm
 * Author URI: https://developer.paytm.com/
 * Tags: Paytm, Paytm Payments, PayWithPaytm, Paytm WooCommerce, Paytm Plugin, Paytm Payment Gateway
 * Requires at least: 4.0.1
 * Tested up to: 5.8
 * Requires PHP: 5.6
 * Text Domain: Paytm Payments
 * WC requires at least: 2.0.0
 * WC tested up to: 4.0
 */



/**
* Add the Gateway to WooCommerce
**/
if ( ! defined( 'ABSPATH' ) )
{
    exit; // Exit if accessed directly
}

require_once(__DIR__.'/includes/PaytmHelper.php');
require_once(__DIR__.'/includes/PaytmChecksum.php');

add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'woocommerce_paytm_add_action_links' );
function woocommerce_paytm_add_action_links ( $links ) {
	
	$settting_url = array(
	 '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=WC_paytm') . '"><b>Settings</b></a>',
	 '<a href="' . PaytmConstants::PLUGIN_DOC_URL . '" target="_blank"><b>Docs</b></a>',
	 );
	return array_merge($settting_url,$links);
}

/* Create table 'paytm_order_data' after install paytm plugin */
if ( function_exists('register_activation_hook') )
register_activation_hook( __FILE__,  'install_paytm_plugin' );
/* Drop table 'paytm_order_data' after uninstall paytm plugin */
if ( function_exists('register_deactivation_hook') )
register_deactivation_hook( __FILE__, 'uninstall_paytm_plugin' );


function install_paytm_plugin(){
	global $wpdb;
	$table_name = $wpdb->prefix . 'paytm_order_data';
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`order_id` int(11) NOT NULL,
			`paytm_order_id` VARCHAR(255) NOT NULL,
			`transaction_id` VARCHAR(255) NOT NULL,
			`status` ENUM('0', '1')  DEFAULT '0' NOT NULL,
			`paytm_response` TEXT,
			`date_added` DATETIME NOT NULL,
			`date_modified` DATETIME NOT NULL,
			PRIMARY KEY (`id`)
		);";			
    $wpdb->query($sql);
}

function uninstall_paytm_plugin(){
	global $wpdb;
	$table_name = $wpdb->prefix . 'paytm_order_data';
    $sql = "DROP TABLE IF EXISTS $table_name";
    $wpdb->query($sql);
    delete_option('woocommerce_paytm_settings');
}
function paytmWoopayment_enqueue_style() {
    wp_enqueue_style('paytmWoopayment', plugin_dir_url( __FILE__ ) . 'assets/css/paytm-payments.css', array(), '', '');
	// $plugin_data = get_plugin_data( __FILE__ );
	// define('PAYTM_VERSION',$plugin_data['Version']);
	define('PAYTM_VERSION','2.6.2');
}
add_action('wp_head', 'paytmWoopayment_enqueue_style');


/*
* check cURL working or able to communicate with Paytm
* http://www.example.com/?paytm_action=curltest
*/
if(isset($_GET['paytm_action']) && sanitize_text_field($_GET['paytm_action']) == "curltest"){
	add_action('the_content', 'curltest');
}

function curltest($content){

	$debug = array();

	if(!function_exists("curl_init")){
		$debug[0]["info"][] = "cURL extension is either not available or disabled. Check phpinfo for more info.";

	// if curl is enable then see if outgoing URLs are blocked or not
	} else {

		// if any specific URL passed to test for
		if(isset($_GET["url"]) && $_GET["url"] != ""){
			$testing_urls = array(esc_url_raw($_GET["url"]));
		
		} else {

			// this site homepage URL
			$server = esc_url( home_url( '/' ));

			$testing_urls = array(
								$server,
								"https://www.gstatic.com/generate_204",
								PaytmHelper::getPaytmURL(PaytmConstants::ORDER_STATUS_URL, 1),
								PaytmHelper::getPaytmURL(PaytmConstants::ORDER_STATUS_URL, 0)
							);
		}
		$testing_urls = array_filter($testing_urls);

		// loop over all URLs, maintain debug log for each response received
		foreach($testing_urls as $key => $url){

			$debug[$key]["info"][] = "Connecting to <b>" . $url . "</b> using cURL";
			
			$response = wp_remote_get($url, array('redirection' => 0));
			
			$http_code = wp_remote_retrieve_response_code($response);

			if ( is_array( $response ) ) {

				$debug[$key]["info"][] = "cURL executed succcessfully.";
				$debug[$key]["info"][] = "HTTP Response Code: <b>". $http_code . "</b>";

			} else {
				$debug[$key]["info"][] = "Connection Failed !!";
				$debug[$key]["info"][] = "Error: <b>" . $response->get_error_message() . "</b>";
			}

			if((!empty($_GET["url"])) || (in_array($url, array(PaytmHelper::getPaytmURL(PaytmConstants::ORDER_STATUS_URL, 1) , PaytmHelper::getPaytmURL(PaytmConstants::ORDER_STATUS_URL, 0))))){
				$debug[$key]["info"][] = "Response: <br/><!----- Response Below ----->" . wp_remote_retrieve_body($response);
			}
		}
	}

	

	$content = "<center><h1>cURL Test for Paytm WooCommerce Plugin</h1></center><hr/>";
	foreach($debug as $k => $v){
		$content .= "<ul>";
		foreach($v["info"] as $info){
			$content .= "<li>".$info."</li>";
		}
		$content .= "</ul>";
		$content .= "<hr/>";
	}

	return $content;
}
/*
* Code to test Curl
*/

if(PaytmConstants::SAVE_PAYTM_RESPONSE){
	// Add a paytm payments box only for shop_order post type (order edit pages)
	add_action( 'add_meta_boxes', 'add_paytm_payment_block' );
	function add_paytm_payment_block(){
		global $wpdb;
		$settings = get_option( "woocommerce_paytm_settings" );
		$post_id = sanitize_text_field($_GET['post']);
		if(! $post_id ) return; // Exit

		$results = getPaytmOrderData($post_id);
		
		// paytm enabled and order is exists with paym_order_data
		if($settings['enabled'] == 'yes' && !empty($results)){
			add_meta_box( '_paytm_response_table', __( 'Paytm Payments' ),
				'_paytm_response_table', 'shop_order', 'normal', 'default',array('results' => $results));
		}  
	}

	function _paytm_response_table($post = array(),$data = array()){

		$table_html = '<div class="" id="paytm_payment_area"><div class="message"></div>';
		$results = $data['args']['results'];
		$table_html .= '<div class="btn-area"><img class="paytm-img-loader" src="'.admin_url('images/loading.gif').'"><button type="button" id="button-paytm-fetch-status" class="button-paytm-fetch-status button">'.__(PaytmConstants::FETCH_BUTTON).'</button></div>';
		$paytm_data = array();
		if(!empty($results)){  		
				$paytm_data = json_decode($results['paytm_response'], true); 			
				if(!empty($paytm_data)){
					$table_html .= '<table class="paytm_payment_block" id="paytm_payment_table">';
					foreach ($paytm_data as $key => $value) {
						$table_html .= '<tr><td>'.$key.'</td><td>' .$value.'</td></tr>';
					}
					$table_html .= '</table>';
					$table_html .= '<input type="hidden" id="paytm_order_id" name="paytm_order_id" value="'.$results['paytm_order_id'].'"><input type="hidden" id="order_data_id" name="order_data_id" value="'.$results['id'].'"><input type="hidden" id="paytm_woo_nonce" name="paytm_woo_nonce" value="'.wp_create_nonce('paytm_woo_nonce').'">';
				}
		}
		$table_html .= '</div>';
		echo $table_html;
	}
	function getPaytmOrderData($order_id){
		global $wpdb;
		$sql = "SELECT * FROM `".$wpdb->prefix ."paytm_order_data` WHERE `order_id` = $order_id ORDER BY `id` DESC LIMIT 1";
		return $wpdb->get_row($sql,"ARRAY_A");
	}

	add_action('admin_head', 'woocommerce_paytm_add_css_js');

	function woocommerce_paytm_add_css_js() {?>
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
			.wp-core-ui .button.button-paytm-fetch-status{float: left; line-height: normal; background: #2b9c2b; color: #fff;
				border-color: #2b9c2b;}
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
					url: "<?php echo admin_url("admin-ajax.php");?>",
					success: function(data) {
						$('.paytm-img-loader').hide();
						if (data.success == true) {
							var html = '';
							$.each(data.response, function (index, value) {
								html += "<tr>";
								html += "<td>" + index + "</td>";
								html += "<td>" + value + "</td>";
								html += "</tr>";
							});
							jQuery('#paytm_payment_table').html(html);
							jQuery('#paytm_payment_area div.message').html('<div class="paytm_response success-box">' + data.message + '</div>');
							
						} else {
							jQuery('#paytm_payment_area div.message').html('<div class="paytm_response error-box">' + data.message + '</div>');
						}
					}
				});
			});
		});
		
		</script>
	<?php }

	add_action( 'wp_ajax_savetxnstatus', 'savetxnstatus' );

	function savetxnstatus(){

		if ( ! wp_verify_nonce( $_POST['paytm_woo_nonce'], 'paytm_woo_nonce' ) ) die ( 'You are not authorised!');

		$settings = get_option( "woocommerce_paytm_settings" );
		$json = array("success" => false, "response" => '', 'message' => __(PaytmConstants::RESPONSE_ERROR));
		
		if(!empty($_POST['paytm_order_id']) && PaytmConstants::SAVE_PAYTM_RESPONSE){
			$reqParams = array(
				"MID" 		=> $settings['merchant_id'],
				"ORDERID" 	=> $_POST['paytm_order_id']
			);

			$reqParams['CHECKSUMHASH'] = PaytmChecksum::generateSignature($reqParams, $settings['merchant_key']);
				
			$retry = 1;
			do{
				$resParams = PaytmHelper::executecUrl(PaytmHelper::getTransactionStatusURL($settings['environment']), $reqParams);
				$retry++;
			} while(!$resParams['STATUS'] && $retry < PaytmConstants::MAX_RETRY_COUNT);

			if(!empty($resParams['STATUS'])){
				$response	=	saveTxnResponse($resParams, $_POST['paytm_order_id'], $_POST['order_data_id']); 
				if($response){
					$message = __(PaytmConstants::RESPONSE_SUCCESS);					
					$json = array("success" => true, "response" => $resParams, 'message' => $message);
				}
			}
		}
		echo json_encode($json);die;
	}

	/**
	* save response in db
	*/
	function saveTxnResponse($data  = array(),$order_id, $id = false){
		global $wpdb;
		if(empty($data['STATUS'])) return false;
		
		$status 			= (!empty($data['STATUS']) && $data['STATUS'] =='TXN_SUCCESS') ? 1 : 0;
		$paytm_order_id 	= (!empty($data['ORDERID'])? $data['ORDERID']:'');
		$transaction_id 	= (!empty($data['TXNID'])? $data['TXNID']:'');
		
		if($id !== false){
			$sql =  "UPDATE `" . $wpdb->prefix . "paytm_order_data` SET `order_id` = '" . $order_id . "', `paytm_order_id` = '" . $paytm_order_id . "', `transaction_id` = '" . $transaction_id . "', `status` = '" . (int)$status . "', `paytm_response` = '" . json_encode($data) . "', `date_modified` = NOW() WHERE `id` = '" . (int)$id . "' AND `paytm_order_id` = '" . $paytm_order_id . "'";
			$wpdb->query($sql);
			return $id;
		}else{
			$sql =  "INSERT INTO `" . $wpdb->prefix . "paytm_order_data` SET `order_id` = '" . $order_id . "', `paytm_order_id` = '" . $paytm_order_id . "', `transaction_id` = '" . $transaction_id . "', `status` = '" . (int)$status . "', `paytm_response` = '" . json_encode($data) . "', `date_added` = NOW(), `date_modified` = NOW()";
			$wpdb->query($sql);
			return $wpdb->insert_id;
		}
	}
}
	add_action('plugins_loaded', 'woocommerce_paytm_init', 0);

	function woocommerce_paytm_init() {
		// If the WooCommerce payment gateway class is not available nothing will return
		if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;

		// WooCommerce payment gateway class to hook Payment gateway
		require_once( plugin_basename( 'class.paytm.php' ) );

		add_filter('woocommerce_payment_gateways', 'woocommerce_add_paytm_gateway' );
		function woocommerce_add_paytm_gateway($methods) {
			$methods[] = 'WC_paytm';
			return $methods;
		}

		/**
		 * Localisation
		 */
		load_plugin_textdomain('wc-paytm', false, dirname( plugin_basename( __FILE__ ) ) . '/languages');

		if(isset($_GET['paytm_response']) && sanitize_text_field($_GET['paytm_response'])){
			add_action('the_content', 'paytmResponseMessage');
		}
		
		add_action('wp_head', 'woocommerce_paytm_front_add_css');

		function woocommerce_paytm_front_add_css() {?>
		<style>
			.paytm_response{padding:15px; margin-bottom: 20px; border: 1px solid transparent; border-radius: 4px; text-align: center;}
			.paytm_response.error-box{color: #a94442; background-color: #f2dede; border-color: #ebccd1;}
			.paytm_response.success-box{color: #155724; background-color: #d4edda; border-color: #c3e6cb;}
		</style>
		<?php } 
		
		function paytmResponseMessage($content){
			return '<div class="paytm_response box '.htmlentities(sanitize_text_field($_GET['type'])).'-box">'.htmlentities(urldecode(sanitize_text_field($_GET['paytm_response']))).'</div>'.$content;
		}
	}
