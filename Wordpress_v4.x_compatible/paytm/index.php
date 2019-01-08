<?php include('encdec_paytm.php');?>
<?php
/**
 * Plugin Name: Paytm Payment Gateway
 * Plugin URI: https://github.com/Paytm-Payments/
 * Description: This plugin allow you to accept payments using Paytm. This plugin will add a Paytm Payment option on WooCommerce checkout page, when user choses Paytm as Payment Method, he will redirected to Paytm website to complete his transaction and on completion his payment, paytm will send that user back to your website along with transactions details. This plugin uses server-to-server verification to add additional security layer for validating transactions. Admin can also see payment status for orders by navigating to WooCommerce > Orders from menu in admin.
 * Version: 1.0
 * Author: Paytm
 * Author URI: http://paywithpaytm.com/
 * Tags: Paytm, Paytm Payments, PayWithPaytm, Paytm WooCommerce, Paytm Plugin, Paytm Payment Gateway
 * Requires at least: 4.0.1
 * Tested up to: 5.0
 * Requires PHP: 5.6
 * Text Domain: Paytm Payments
 */

add_action('plugins_loaded', 'woocommerce_paytm_init', 0);

function woocommerce_paytm_init() {

	if ( !class_exists( 'WC_Payment_Gateway' ) ) return;

	/**
	 * Localisation
	 */
	load_plugin_textdomain('wc-paytm', false, dirname( plugin_basename( __FILE__ ) ) . '/languages');
	if(isset($_GET['paytm_response'])){
		add_action('the_content', 'paytmShowMessage');
	}
   
	function paytmShowMessage($content){
		return '<style>.paytm_response{padding: 15px;margin-bottom: 20px;border: 1px solid transparent;border-radius: 4px;text-align: center;}.paytm_response.error-box{color: #a94442;background-color: #f2dede;border-color: #ebccd1;} .paytm_response.success-box{color: #3c763d;background-color: #dff0d8;border-color: #d6e9c6;}</style><div class="paytm_response box '.htmlentities($_GET['type']).'-box">'.htmlentities(urldecode($_GET['paytm_response'])).'</div>'.$content;
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

			$this->promo_code_status = $this->settings['promo_code_status'];
			$this->promo_code_validation = $this->settings['promo_code_validation'];
			$this->promo_codes = $this->settings['promo_codes'];

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
			$checkout_page_id = get_option('woocommerce_checkout_page_id');
			// fallback
			$checkout_page_id = (int) $checkout_page_id > 0 ? $checkout_page_id : 7;
			return get_site_url() . '/?page_id='.$checkout_page_id.'&wc-api=WC_paytm';
		}

		function init_form_fields(){   

			$this->form_fields = array(
				'enabled'			=> array(
					'title' 			=> __('Enable/Disable'),
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
					'options'		=> $this->get_pages('Default Page'),
					'description'	=> "Page that customer will see after successful transaction"
				),
				'promo_code_status' => array(
					'title'			=> __('Promo Code Status'),
					'type'			=> 'select',
					'options'		=> array("0" => "Disabled", "1" => "Enabled"),
					'default'		=> '0',
					'description'	=> __('Enabling this will show Promo Code field at Checkout.'),
				),
				'promo_code_validation' => array(
					'title'			=> __('Local Validation'),
					'type'			=> 'select',
					'options'		=> array("0" => "Disabled", "1" => "Enabled"),
					'default'		=> '0',
					'description'	=> __('Transaction will be failed in case of Promo Code failure at Paytm\'s end.'),
					'desc_tip'		=> _('Validate applied Promo Code before proceeding to Paytm payment page.')
				),
				'promo_codes' => array(
					'title'			=> __('Promo Codes'),
					'label'			=> __('Enable'),
					'type'			=> 'text',
					'description'	=> __('Use comma ( , ) to separate multiple codes i.e. FB50,CASHBACK10 etc.'),
					'desc_tip'		=> __('These promo codes must be configured with your Paytm MID.')
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

			$last_updated = "";
			$path = plugin_dir_path( __FILE__ ) . "/paytm_version.txt";
			if(file_exists($path)){
				$handle = fopen($path, "r");
				if($handle !== false){
					$date = fread($handle, 10); // i.e. DD-MM-YYYY or 25-04-2018
					$last_updated = '<p>Last Updated: '. date("d F Y", strtotime($date)) .'</p>';
				}
			}

			$footer_text = '<div style="text-align: center;"><hr/>'.$last_updated.'<p>WooCommerce Version: ' .WOOCOMMERCE_VERSION.'</p></div>';

			echo $footer_text;

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

					// add border around promo code configurations to keep them separate
					jQuery("select[name=\"woocommerce_paytm_promo_code_status\"]").parents("tr").css("border-top", "1px solid black");
					
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

				// $order_id = substr($order_id, strpos($order_id, "-") + 1); // just for testing

				$responseDescription = (!empty($_REQUEST['RESPMSG'])) ? $_REQUEST['RESPMSG'] :"";

				if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
					$order = new WC_Order($order_id);
				} else {
					$order = new woocommerce_order($order_id);
				}

				$isValidChecksum = PaytmPayment::verifychecksum_e($_POST, $this->secret_key, $_POST['CHECKSUMHASH']);

				if ($isValidChecksum) {
					
					if($_POST['STATUS'] == 'TXN_SUCCESS'){
						// Create an array having all required parameters for status query.
						$requestParamList = array(
							"MID"		=> $this->merchantIdentifier,
							"ORDERID"	=> $order_id
						);

						// $requestParamList["ORDERID"] = $_POST["ORDERID"]; // just for testing

						$requestParamList['CHECKSUMHASH'] = PaytmPayment::getChecksumFromArray($requestParamList, $this->secret_key);

						$responseParamList = PaytmPayment::callNewAPI($this->transaction_status_url, $requestParamList);
			
						if(!empty($responseParamList['STATUS'])){

							if($responseParamList['STATUS'] == 'TXN_SUCCESS' 
								&& $responseParamList['TXNAMOUNT'] == $_POST['TXNAMOUNT']) {
									if($order->status !=='completed'){
									
										$this->msg['message'] = "Thank you for your order. Your transaction has been successful.";
										$this->msg['class'] = 'success';
										
										if($order->status == 'processing'){
	
										} else {
											$order->payment_complete();
											$order->add_order_note($this->msg['message']);
	
										}
									}
							}else{
								// Txn Failed
								$msg = 'Your transaction has been failed.';
								if($responseParamList['TXNAMOUNT'] != $_POST['TXNAMOUNT']) {
									$msg = "Security Error. Amount mismatch!";
								} else if(isset($responseParamList['RESPMSG']) && !empty($responseParamList['RESPMSG'])){
									$msg .= ' Reason: '.$responseParamList['RESPMSG'];
								} else {
									$msg .= ' Please contact to seller if money has deducted.';
								}
								$this->txn_failure($order, $msg);
							}
						}else{
							// S2S failed //
							$msg = 'It seems some issue in server to server communication. Kindly connect with administrator.';
							$this->txn_failure($order, $msg);
						}
					
					}else{
						// txn failed
						$msg = "Your transaction has been failed.";
						if(!empty($responseDescription)){
							$msg .= " Reason : " . $responseDescription;
						} else {
							$msg .= ' Please contact to seller if money has deducted.';
						}
						$this->txn_failure($order, $msg);
					}

				}else{
					// checksum failed
					$msg = 'Security Error. Checksum Failed!!';
					$this->txn_failure($order, $msg);
				}

				add_action('the_content', array(&$this, 'paytmShowMessage'));
				
				// Redirection after paytm payments response.
				if ( '' == $this->redirect_page_id || 0 == $this->redirect_page_id ) {
					if('success' == $this->msg['class']) {
						$redirect_url = $order->get_checkout_order_received_url();
					}else{
						// $redirect_url = wc_get_checkout_url();
						// $redirect_url = get_home_url();
						$redirect_url = $order->get_view_order_url();
					}
				} else {
					$redirect_url = get_permalink( $this->redirect_page_id );
				}

				$woocommerce->cart->empty_cart();

				//For wooCoomerce 2.0
				$redirect_url = add_query_arg(
												array(
													'paytm_response'=> urlencode($this->msg['message']),
													'type'=>$this->msg['class']
												), $redirect_url
											);

				wp_redirect( $redirect_url );
				exit;
				}
		}
		function txn_failure($order, $msg = ''){
			$this->msg['class'] = 'error';
			$this->msg['message'] = $msg;
			$order->update_status('failed');
			$order->add_order_note('Failed');
			$order->add_order_note($this->msg['message']);
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


			// $order_id = "TEST_".strtotime("now")."_ORDERID-".$order_id; // just for testing


			// decode html entity as & would have converted to &amp; before saving to database
			$callback_url = html_entity_decode($this->callback_url);

			$post_variables = array(
					"MID"				=> $this->merchantIdentifier,
					"ORDER_ID"			=> sanitize_text_field($order_id),
					"CUST_ID"			=> sanitize_text_field($email),
					"TXN_AMOUNT"		=> sanitize_text_field($order->order_total),
					"CHANNEL_ID"		=> $this->channel_id,
					"INDUSTRY_TYPE_ID"	=> $this->industry_type,
					"WEBSITE"			=> $this->website,
					"EMAIL"				=> sanitize_email($email),
					"MOBILE_NO"			=> sanitize_text_field($mobile_no),
					"CALLBACK_URL"		=> sanitize_text_field($callback_url)
			);

			
			$post_variables["CHECKSUMHASH"] = PaytmPayment::getChecksumFromArray($post_variables, $this->secret_key);

			// echo "<PRE>".http_build_query($post_variables);exit;

			$paytm_form_fields = "";
			foreach($post_variables as $k=>$v){
				$paytm_form_fields .= '<input type="hidden" name="'. $k .'" value="'. $v .'"/>';
			}


			if($this->promo_code_status) {
				$this->show_promo_code = true;
			} else {
				$this->show_promo_code = false;
			}

			if($this->show_promo_code == true) {
				return 
				'<form action="'.$this->gateway_url.'" method="post" id="paytm_form_redirect">
								' . $paytm_form_fields . '</form>
				<div id="promo-code-section" style="margin-bottom:10px;">
					<input type="text" id="promo_code" name="promo_code" placeholder="Promo Code" style="display:block; width:50%; float:left;">
					<button id="btn_promo_code" class="btn btn-primary" style="display:block; float:left;" type="button">Apply</button>
					<div style="clear:both;"></div>
				</div>
				<div class="buttons">
					<div class="pull-right">
				   	<input type="submit" class="button-alt" id="submit_paytm_form_redirect" value="'.__('Pay via paytm').'" />
						<a class="button cancel" href="'.$order->get_cancel_order_url().'">
							'.__('Cancel order &amp; restore cart').'
						</a>
					</div>
				</div>
				<style>
				#promo-code-section.has-error input{
					border:1px solid #f56b6b;
				}

				#promo-code-section input[disabled]{
					cursor: not-allowed;
					background-color: #eee;
					opacity: 1;
 				}
				</style>
				<script type="text/javascript">
				/*
				* Promo Code functionality starts here
				*/
				var original_checksum = "'.$post_variables["CHECKSUMHASH"].'";

				jQuery(document).ready(function($){
					$("#btn_promo_code").click(function(){

						$("#promo-code-section.has-error").removeClass("has-error");
						$("#promo-code-section .text-danger, #promo-code-section .text-success").remove();

						// if some promo code already applied and now user requests to remove it
						if($(this).hasClass("removePromoCode")){

							// remove promo code from form params
							$("form#paytm_form_redirect input[name=PROMO_CAMP_ID]").remove();
							$("form#paytm_form_redirect input[name=CHECKSUMHASH]").val(original_checksum);

							// enable input to allow user to enter promo code
							$("#promo_code").prop("disabled", false).val("");
							$("#btn_promo_code").addClass("btn-primary").removeClass("btn-danger").removeClass("removePromoCode").text("Apply");

						} else {

							if($("#promo_code").val().trim() == "") {
								$("#promo_code").parent().addClass("has-error");
								return;
							};

							$.ajax({
								url: "'.admin_url( 'admin-ajax.php?action=apply_coupon' ).'",
								type: "post",
								dataType: "json",
								data: $("form#paytm_form_redirect").serialize() + "&promo_code="+$("#promo_code").val(),
								success: function(res){
									if(res.success == true){
										// remove old input if there is already exists, to avoid duplicate inputs
										$("form#paytm_form_redirect input[name=PROMO_CAMP_ID]").remove();

										// add promo code input to form post
										$("form#paytm_form_redirect").append("<input type=\"hidden\" name=\"PROMO_CAMP_ID\" value=\"\"/>");

										// add promo code value
										$("form#paytm_form_redirect input[name=PROMO_CAMP_ID").val($("#promo_code").val());

										// bind new generated checksum
										$("form#paytm_form_redirect input[name=CHECKSUMHASH]").val(res.CHECKSUMHASH);

										$("#promo-code-section").append("<span class=\"text-success\">"+ res.message +"</span>");

										$("#promo_code").prop("disabled", true);
										$("#btn_promo_code").removeClass("btn-primary").addClass("btn-danger").addClass("removePromoCode").text("Remove");
									} else {
										$("#promo-code-section").addClass("has-error").append("<span class=\"text-danger\">"+ res.message +"</span>");
									}
								}
							});
						}
					});

					$("#submit_paytm_form_redirect").click(function(){
						document.getElementById("paytm_form_redirect").submit();
					});
				});
				/*
				* Promo Code functionality starts here
				*/
				</script>
				';
				} else {

					return 
					'<form action="'.$this->gateway_url.'" method="post" id="paytm_form_redirect">
						' . $paytm_form_fields . '
						<input type="submit" class="button-alt" id="submit_paytm_form_redirect" value="'.__('Pay via paytm').'" />
						<a class="button cancel" href="'.$order->get_cancel_order_url().'">
							'.__('Cancel order &amp; restore cart').'
						</a>
						<script type="text/javascript">
						jQuery(function(){
							jQuery("body").block({
							message: "<img src=\"'.plugins_url( 'images/loader.gif', __FILE__ ).'\" alt=\"Redirecting…\" style=\"float:left; margin-right: 10px;\" />'.__('Thank you for your order. We are now redirecting you to paytm to make payment.').'",
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
							
							document.getElementById("paytm_form_redirect").submit();

							});
						</script>
					</form>';
				}
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


	/*
	* Promo Code functions here
	*/
	add_action( "wp_ajax_nopriv_apply_coupon" , 'apply_coupon' );
	add_action( "wp_ajax_apply_coupon" , 'apply_coupon' );

	function apply_coupon() {
		
		$settings = get_option( "woocommerce_paytm_settings", null );
		// echo "<PRE>";print_r($settings);	echo __LINE__;exit;

		if(isset($_POST["promo_code"]) && trim(sanitize_text_field($_POST["promo_code"])) != "") {

			$json = array();

			// if promo code local validation enabled
			if($settings["promo_code_validation"]){

				$promo_codes = explode(",", $settings["promo_codes"]);

				$promo_code_found = false;

				foreach($promo_codes as $key=>$val){
					// entered promo code should matched
					if(trim($val) == trim(sanitize_text_field($_POST["promo_code"]))) {
						$promo_code_found = true;
						break;
					}
				}

			} else {
				$promo_code_found = true;
			}

			if($promo_code_found){
				$json = array("success" => true, "message" => "Applied Successfully");
				
				$reqParams = $_POST;

				if(isset($reqParams["promo_code"])){
					// PROMO_CAMP_ID is key for Promo Code at Paytm's end
					$reqParams["PROMO_CAMP_ID"] = sanitize_text_field($reqParams["promo_code"]);
				
					// unset promo code sent in request	
					unset($reqParams["promo_code"]);

					// unset CHECKSUMHASH
					unset($reqParams["CHECKSUMHASH"]);
				}

				// create a new checksum with Param Code included and send it to browser
				$json['CHECKSUMHASH'] = PaytmPayment::getChecksumFromArray($reqParams, $settings["secret_key"]);
			} else {
				$json = array("success" => false, "message" => "Incorrect Promo Code");
			}

			echo json_encode($json); exit;
		}
	}
	/*
	* Promo Code functions here
	*/


	/*
	* Code to test Curl
	*/
	if(isset($_GET['paytm_action']) && $_GET['paytm_action'] == "curltest"){
		add_action('the_content', 'curltest');
	}

	function curltest($content){

		// phpinfo();exit;
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
				$server = get_site_url();

				$testing_urls = array(
										$server,
										"https://www.gstatic.com/generate_204",
										get_option('transaction_status_url')
									);
			}

			// loop over all URLs, maintain debug log for each response received
			foreach($testing_urls as $key=>$url){

				$debug[$key]["info"][] = "Connecting to <b>" . $url . "</b> using cURL";
				
				$response = wp_remote_get($url);

				if ( is_array( $response ) ) {

					$http_code = wp_remote_retrieve_response_code($response);
					$debug[$key]["info"][] = "cURL executed succcessfully.";
					$debug[$key]["info"][] = "HTTP Response Code: <b>". $http_code . "</b>";

					// $debug[$key]["content"] = $res;

				} else {
					$debug[$key]["info"][] = "Connection Failed !!";
					$debug[$key]["info"][] = "Error: <b>" . $response->get_error_message() . "</b>";
					//break;
				}
			}
		}

		$content = "<center><h1>cURL Test for Paytm WooCommerce Plugin</h1></center><hr/>";
		foreach($debug as $k=>$v){
			$content .= "<ul>";
			foreach($v["info"] as $info){
				$content .= "<li>".$info."</li>";
			}
			$content .= "</ul>";

			// echo "<div style='display:none;'>" . $v["content"] . "</div>";
			$content .= "<hr/>";
		}

		return $content;
	}
	/*
	* Code to test Curl
	*/


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