<?php include('encdec_paytm.php');?>
<?php
/*
Plugin Name: WooCommerce paytm gateway
Plugin URI: http://paytm.com/
Description: Paytm Payment Gateway with Check Status.
Version: 0.2
Author: Paytm

	License: GNU General Public License v3.0
	License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

add_action('plugins_loaded', 'woocommerce_paytm_init', 0);

function woocommerce_paytm_init() {

	if ( !class_exists( 'WC_Payment_Gateway' ) ) return;

	/**
	 * Localisation
	 */
	load_plugin_textdomain('wc-paytm', false, dirname( plugin_basename( __FILE__ ) ) . '/languages');
	if(isset($_GET['msg'])){
		add_action('the_content', 'paytmShowMessage');
	}
   
	function paytmShowMessage($content){
		return '<div class="box '.htmlentities($_GET['type']).'-box">'.htmlentities(urldecode($_GET['msg'])).'</div>'.$content;
	}
	/**
	 * Gateway class
	 */
	class WC_paytm extends WC_Payment_Gateway {
		
		protected $msg = array();

		public function __construct() {
			// Go wild in here
			$this->id = 'paytm';
			$this->method_title = __('Paytm');
			$this->icon = WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/images/logo.gif';
			$this->has_fields = false;
			$this->init_form_fields();
			$this->init_settings();
			$this->title = $this->settings['title'];
			$this->description = $this->settings['description'];
			$this->merchantIdentifier = $this->settings['merchantIdentifier'];
			$this->secret_key = $this->settings['secret_key'];			
			$this->gateway_url = $this->settings['gateway_url'];
			$this->transaction_status_url = $this->settings['transaction_status_url'];
			$this->industry_type = $this->settings['industry_type'];
			$this->channel_id = $this->settings['channel_id'];
			$this->website = $this->settings['website'];
			$this->redirect_page_id = $this->settings['redirect_page_id'];
			$this->callback_url = $this->settings['callback_url'];
			// $this->log = $this->settings['log'];
			$this->msg['message'] = "";
			$this->msg['class'] = "";	
			
			add_action('init', array(&$this, 'check_paytm_response'));
			//update for woocommerce >2.0
			add_action( 'woocommerce_api_' . strtolower( get_class( $this ) ), array( $this, 'check_paytm_response' ) );
			add_action('valid-paytm-request', array(&$this, 'successful_request')); // this save
			if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
			 } else {
				add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
			}
			add_action('woocommerce_receipt_paytm', array(&$this, 'receipt_page'));
		  //  add_action('woocommerce_thankyou_paytm',array(&$this, 'thankyou_page'));
		}

		private function getDefaultCallbackUrl(){
			return get_site_url() . '/?page_id=7&wc-api=WC_paytm';
		}

		function init_form_fields(){   

			$this->form_fields = array(
				'enabled'			=> array(
					'title' 		=> __('Enable/Disable'),
					'type' 			=> 'checkbox',
					'label'			=> __('Enable Paytm Payments.'),
					'default'		=> 'no'
				),
				'title' => array(
					'title'			=> __('Title'),
					'type'			=> 'text',
					'description'	=> __('This controls the title which the user sees during checkout.'),
					'default'		=> __('Paytm'),
				),
				'description' => array(
					'title'			=> __('Description'),
					'type'			=> 'textarea',
					'description'	=> __('This controls the description which the user sees during checkout.'),
					'default'		=> __('The best payment gateway provider in India for e-payment through credit card, debit card & netbanking.')
				),
				'merchantIdentifier'=> array(
					'title'			=> __('Merchant Identifier'),
					'type' 			=> 'text',
					'description'	=> __('Merchant Id Provided by Paytm')
				),
				'secret_key' => array(
					'title'			=> __('Secret Key'),
					'type'			=> 'text',
					'description'	=> __('Merchant Secret Key Provided by Paytm'),
				),
				'website' => array(
					'title'			=> __('Website'),
					'type'			=> 'text',
					'description'	=> __('Website Name Provided by Paytm'),
				),
				'industry_type' => array(
					'title'			=> __('Industry Type'),
					'type'			=> 'text',
					'description'	=> __('Industry Type Provided by Paytm'),
				),
				'channel_id' => array(
					'title'			=> __('Channel ID'),
					'type'			=> 'text',
					'default'		=> 'WEB',
					'description'	=> __('Channel ID Provided by Paytm'),
				),
				'gateway_url' => array(
					'title'			=> __('Transaction URL'),
					'type'			=> 'text',
					'description'	=> __('Transaction URL Provided by Paytm'),
				),
				'transaction_status_url' => array(
					'title'			=> __('Transaction Status Url'),
					'type'			=> 'text',
					'description'	=> __('Transaction Status URL Provided by Paytm')
				),
				'custom_callback_url' => array(
					'title'			=> __('Custom Callback URL'),
					'type'			=> 'select',
					'options'		=> array("0" => "Disabled", "1" => "Enabled"),
					'description'	=> __('Enable this if you want to change Default Paytm Callback URL'),
					'default'		=> '0'
				),
				'callback_url' => array(
					'title'			=> __('Callback URL'),
					'type'			=> 'text',
					'default'		=> $this->getDefaultCallbackUrl(),
				),				
				'redirect_page_id' => array(
					'title'			=> __('Return Page'),
					'type'			=> 'select',
					'options'		=> $this->get_pages('Select Page'),
					'description'	=> "Page that customer will see after successful transaction"
				),
				/*
				'log' => array(
					'title'			=> __('Do you want to log'),
					'type'			=> 'checkbox',
					'label'			=> __('Select to enable Log'),
					'default'		=> "no"
				)
				*/
			);
		}
		
		
		/**
		 * Admin Panel Options
		 * - Options for bits like 'title'
		 **/
		public function admin_options(){
			echo '<h3>'.__('Paytm Payment Gateway').'</h3>';
			echo '<p>'.__('Online payment solutions for all your transactions by Paytm').'</p>';


			
			echo '<table class="form-table">';
			$this->generate_settings_html();
			echo '</table>';

			$path = plugin_dir_path( __FILE__ ) . "/paytm_version.txt";
			if(file_exists($path)){
				$handle = fopen($path, "r");
				if($handle !== false){
					$date = fread($handle, 10); // i.e. DD-MM-YYYY or 25-04-2018
					echo '<d>Last Updated: '. date("d F Y", strtotime($date)) .'</p>';
				}
			}


			echo '<script>
					var default_callback_url = "'. $this->getDefaultCallbackUrl() .'";
					function toggleCallbackUrl(){
						if(jQuery("select[name=\"woocommerce_paytm_custom_callback_url\"]").val() == "1"){
							jQuery("input[name=\"woocommerce_paytm_callback_url\"]").prop("readonly", false).parents("tr").removeClass("hidden");
						} else {
							jQuery("input[name=\"woocommerce_paytm_callback_url\"]").val(default_callback_url).prop("readonly", true).parents("tr").addClass("hidden");
						}
					}

					jQuery(document).on("change", "select[name=\"woocommerce_paytm_custom_callback_url\"]", function(){
						toggleCallbackUrl();
					});
					toggleCallbackUrl();
				</script>';
		}

		/**
		 *  There are no payment fields for paytm, but we want to show the description if set.
		 **/
		function payment_fields(){
			if($this->description) echo wpautop(wptexturize($this->description));
		}

		/**
		 * Receipt Page
		 **/
		function receipt_page($order){
			echo '<p>'.__('Thank you for your order, please click the button below to pay with paytm.').'</p>';
			echo $this->generate_paytm_form($order);
		}

		/**
		 * Process the payment and return the result
		 **/
		function process_payment($order_id){
			if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
				$order = new WC_Order($order_id);
			 } else {
				$order = new woocommerce_order($order_id);
			}
			return array('result' => 'success', 'redirect' => add_query_arg('order',
				$order->id, add_query_arg('key', $order->order_key, $order->get_checkout_payment_url( true )))
			);
		}
		
		
		/**
		 * Check for valid paytm server callback // response processing //
		 **/
		function check_paytm_response(){	
			global $woocommerce;			
			if(isset($_REQUEST['ORDERID']) && isset($_REQUEST['RESPCODE'])){
				
				$order_id = $_POST['ORDERID'];

				// $order_id = "28"; // just for testing					

				$responseDescription = $_REQUEST['RESPMSG'];

				if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
					$order = new WC_Order($order_id);
				} else {
					$order = new woocommerce_order($order_id);
				}
				
				/*
				if($this->log == "yes") {
					error_log("Response Code = " . $_REQUEST['RESPCODE']);
				}
				*/

				$this->msg['class'] = 'error';
				$this->msg['message'] = "Thank you for shopping with us. However, the transaction has been Failed For Reason  : " . $responseDescription;

				if($_REQUEST['RESPCODE'] !== 01) { // success
					
					$order_amount = $order->order_total;

					// echo "<PRE>".$order->order_total;print_r($_POST);print_r($order);exit;

					if($_REQUEST['TXNAMOUNT'] == $order_amount){
						
						/*
						if($this->log == "yes") {
							error_log("amount matched");
						}
						*/
						
						$bool = "FALSE";
						$bool = verifychecksum_e($_POST, $this->secret_key, $_POST['CHECKSUMHASH']);

						/*
						//$newcheck = Checksum::calculateChecksum($this->secret_key, $all);
						if($this->log == "yes") {
							error_log("calculated checksum = " . $newch . " and checksum received = " . $_REQUEST['checksum']);
						}
						*/

						if ($bool == "TRUE") {
							
							// Create an array having all required parameters for status query.
							$requestParamList = array(
														"MID"		=> $this->merchantIdentifier,
														"ORDERID"	=> $order_id
													);
							
							// $requestParamList["ORDERID"] = $_POST["ORDERID"]; // jsut for testing

							$requestParamList['CHECKSUMHASH'] = getChecksumFromArray($requestParamList, $this->secret_key);
							
							$responseParamList = callNewAPI($this->transaction_status_url, $requestParamList);

							// echo "<PRE>";print_r($responseParamList);exit;

							if($responseParamList['STATUS'] == 'TXN_SUCCESS' 
								&& $responseParamList['TXNAMOUNT'] == $_POST['TXNAMOUNT']) {
								
								if($order->status !=='completed'){
									
									// error_log("SUCCESS");
									
									$this->msg['message'] = "Thank you for your order . Your transaction has been successful.";
									$this->msg['class'] = 'success';
									
									if($order->status == 'processing'){

									} else {
										$order->payment_complete();
										$order->add_order_note('Mobile Wallet payment successful');
										$order->add_order_note($this->msg['message']);
										$woocommerce->cart->empty_cart();

									}
								}
							
							} else {
								$this->msg['class'] = 'error';
								$this->msg['message'] = "It seems some issue in server to server communication. Kindly connect with administrator.";
								$order->update_status('failed');
								$order->add_order_note('Failed');
								$order->add_order_note($this->msg['message']);
							}						
						
						} else {
							// server to server failed while call//
							//error_log("api process failed");	
							$this->msg['class'] = 'error';
							$this->msg['message'] = "Severe Error Occur.";
							$order->update_status('failed');
							$order->add_order_note('Failed');
							$order->add_order_note($this->msg['message']);
						}
						
					} else {
						// Order mismatch occur //
						//error_log("order mismatch");	
						$this->msg['class'] = 'error';
						$this->msg['message'] = "Order Mismatch Occur";
						$order->update_status('failed');
						$order->add_order_note('Failed');
						$order->add_order_note($this->msg['message']);
						
					}					
				
				} else {
					$order->update_status('failed');
					$order->add_order_note('Failed');
					$order->add_order_note($responseDescription);
					$order->add_order_note($this->msg['message']);
				
				}

				add_action('the_content', array(&$this, 'paytmShowMessage'));
				
				$redirect_url = $order->get_checkout_order_received_url();
				
				//For wooCoomerce 2.0
				$redirect_url = add_query_arg(
												array(
													'msg'=> urlencode($this->msg['message']),
													'type'=>$this->msg['class']
												), $redirect_url
											);

				wp_redirect( $redirect_url );
				exit;		
			} 
		}
		
		
		/**
		 * Generate paytm button link
		 **/
		public function generate_paytm_form($order_id){
			global $woocommerce;

			if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
				$order = new WC_Order($order_id);
			} else {
				$order = new woocommerce_order($order_id);
			}
			
			$email = '';
			try{
				$email = $order->billing_email;
			}catch(Exception $e){
			
			}
			
			$mobile_no = '';
			try{
				$mobile_no = preg_replace('#[^0-9]{0,13}#is','',$order->billing_phone);
			}catch(Exception $e){
			
			}

			// $order_id = "RHL_".strtotime("now")."_".$order_id; // just for testing


			// decode html entity as & would have converted to &amp; before saving to database
			$callback_url = html_entity_decode($this->callback_url);

			$post_variables = array(
					"MID"				=> $this->merchantIdentifier,
					"ORDER_ID"			=> $order_id,
					"CUST_ID"			=> $email,
					"TXN_AMOUNT"		=> $order->order_total,
					"CHANNEL_ID"		=> $this->channel_id,
					"INDUSTRY_TYPE_ID"	=> $this->industry_type,
					"WEBSITE"			=> $this->website,
					"EMAIL"				=> $email,
					"MOBILE_NO"			=> $mobile_no,
					"CALLBACK_URL"		=> $callback_url
			);

			
			$post_variables["CHECKSUMHASH"] = getChecksumFromArray($post_variables, $this->secret_key);

			// echo "<PRE>".http_build_query($post_variables);exit;

			$paytm_args_array = array();
			foreach($post_variables as $k=>$v){
				$paytm_args_array[] = "<input type='hidden' name='". $k ."' value='". $v ."'/>";
			}

			return '<form action="'.$this->gateway_url.'" method="post" id="paytm_payment_form">
						' . implode('', $paytm_args_array) . '
						<input type="submit" class="button-alt" id="submit_paytm_payment_form" value="'.__('Pay via paytm').'" />
						<a class="button cancel" href="'.$order->get_cancel_order_url().'">
							'.__('Cancel order &amp; restore cart').'
						</a>
						<script type="text/javascript">
						jQuery(function(){
							jQuery("body").block({
							message: "<img src=\"'.$woocommerce->plugin_url().'/assets/images/ajax-loader.gif\" alt=\"Redirecting…\" style=\"float:left; margin-right: 10px;\" />'.__('Thank you for your order. We are now redirecting you to paytm to make payment.').'",
								overlayCSS: {
									background: "#fff",
									opacity: 0.6
								}, css: {
									padding: 20,
									textAlign: "center",
									color: "#555",
									border: "3px solid #aaa",
									backgroundColor: "#fff",
									cursor: "wait",
									lineHeight: "32px"
								}
							});
							
							jQuery("#submit_paytm_payment_form").click();

							});
						</script>
					</form>';
		}


		/*
		 * End paytm Essential Functions
		 **/
		// get all pages
		
		function get_pages($title = false, $indent = true) {
			$wp_pages = get_pages('sort_column=menu_order');
			$page_list = array();
			if ($title) $page_list[] = $title;
			foreach ($wp_pages as $page) {
				$prefix = '';
				// show indented child pages?
				if ($indent) {
					$has_parent = $page->post_parent;
					while($has_parent) {
						$prefix .=  ' - ';
						$next_page = get_page($has_parent);
						$has_parent = $next_page->post_parent;
					}
				}
				// add to page list array array
				$page_list[$page->ID] = $prefix . $page->post_title;
			}
			return $page_list;
		}

	}


	/**
	 * Add the Gateway to WooCommerce
	 **/
	function woocommerce_add_paytm_gateway($methods) {
		$methods[] = 'WC_paytm';
		return $methods;
	}

	add_filter('woocommerce_payment_gateways', 'woocommerce_add_paytm_gateway' );
}
?>