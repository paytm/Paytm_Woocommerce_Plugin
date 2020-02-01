<?php
/**
 * Gateway class
 */
class WC_paytm extends WC_Payment_Gateway {
	
	protected $msg = array();

	public function __construct() {
		// Go wild in here
		$this->id 							= PaytmConstants::ID;
		$this->method_title 				= PaytmConstants::METHOD_TITLE;
		$this->method_description 			= PaytmConstants::METHOD_DESCRIPTION;
		$this->icon 						= plugins_url('images/paytm.png' , __FILE__);
		$this->has_fields 					= false;

		$this->init_form_fields();
		$this->init_settings();

		$this->title 						= $this->getSetting('title');
		$this->description 					= $this->getSetting('description');

		$this->msg = array('message' => '', 'class' => '');
		
		$this->initHooks();
	}

	private function initHooks(){
		add_action('init', array(&$this, 'check_paytm_response'));
		//update for woocommerce >2.0
		add_action('woocommerce_api_' . strtolower( get_class( $this ) ), array($this, 'check_paytm_response'));
		if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
			} else {
			add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
		}
		add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
	}
	
	private function getSetting($key)
	{
		return $this->settings[$key];
	}

	private function getCallbackUrl(){
		if(!empty(PaytmConstants::CUSTOM_CALLBACK_URL)){
			return PaytmConstants::CUSTOM_CALLBACK_URL;
		}else{
			$checkout_page_id = get_option('woocommerce_checkout_page_id');
			$checkout_page_id = (int) $checkout_page_id > 0 ? $checkout_page_id : 7;
			return get_site_url() . '/?page_id='.$checkout_page_id.'&wc-api=WC_paytm';
		}	
	}

	public function init_form_fields(){

		$this->form_fields = array(
			'enabled'			=> array(
				'title' 			=> __('Enable/Disable', $this->id),
				'type' 			=> 'checkbox',
				'label'			=> __('Enable Paytm Payments.', $this->id),
				'default'		=> 'no'
			),
			'title' => array(
				'title'			=> __('Title', $this->id),
				'type'			=> 'text',
				'description'	=> __('This controls the title which the user sees during checkout.', $this->id),
				'default'		=> __(PaytmConstants::TITLE, $this->id),
			),
			'description' => array(
				'title'			=> __('Description', $this->id),
				'type'			=> 'textarea',
				'description'	=> __('This controls the description which the user sees during checkout.', $this->id),
				'default'		=> __(PaytmConstants::DESCRIPTION, $this->id)
			),
			'merchant_id'=> array(
				'title'			=> __('Merchant ID'),
				'type' 			=> 'text',
				'custom_attributes' => array( 'required' => 'required' ),
				'description'	=> __('Enter your Merchant ID provided by Paytm', $this->id)
			),
			'merchant_key' => array(
				'title'			=> __('Merchant Key'),
				'type'			=> 'text',
				'custom_attributes' => array( 'required' => 'required' ),
				'description'	=> __('Enter your Merchant Key provided by Paytm', $this->id),
			),
			'website' => array(
				'title'			=> __('Website Name'),
				'type'			=> 'text',
				'custom_attributes' => array( 'required' => 'required' ),
				'description'	=> __('Enter your Website Name provded by Paytm', $this->id),
			),
			'industry_type' => array(
				'title'			=> __('Industry Type'),
				'type'			=> 'text',
				'custom_attributes' => array( 'required' => 'required' ),
				'description'	=> __('Eg. Retail, Entertainment etc.', $this->id),
			),
			'environment' => array(
				'title'			=> __('Environment'), $this->id,
				'type'			=> 'select',
				'custom_attributes' => array( 'required' => 'required' ),
				'options'		=> array("0" => "Staging", "1" => "Production"),
				'description'	=> __('Select environment.', $this->id),
				'default'		=> '0'
			)
		);
	}
	
	
	/**
	 * Admin Panel Options
	 * - Options for bits like 'title'
	 **/
	public function admin_options(){
		echo '<h3>'.__('Paytm Payment Gateway', $this->id).'</h3>';
		echo '<p>'.__('Online payment solutions for all your transactions by Paytm', $this->id).'</p>';	

		// Check cUrl is enabled or not
		$curl_version = PaytmHelper::getcURLversion();

		if(empty($curl_version)){
			echo '<div class="paytm_response error-box">'. PaytmConstants::ERROR_CURL_DISABLED .'</div>';
		}
		
		// Transaction URL is not working properly or not able to communicate with paytm
		if(!empty(PaytmHelper::getTransactionStatusURL($this->getSetting('environment')))){
			$response = (array)wp_remote_get(PaytmHelper::getTransactionStatusURL($this->getSetting('environment')));
			if(!empty($response['errors'])){
				echo '<div class="paytm_response error-box">'. PaytmConstants::ERROR_CURL_WARNING .'</div>';
			}
		}

		echo '<table class="form-table">';
			$this->generate_settings_html();
		echo '</table>';
	
		$last_updated = date("d F Y", strtotime(PaytmConstants::LAST_UPDATED)) .' - '.PaytmConstants::PLUGIN_VERSION;

		$footer_text = '<div style="text-align: center;"><hr/>';
		$footer_text .= '<strong>'.__('PHP Version').'</strong> '. PHP_VERSION . ' | ';
		$footer_text .= '<strong>'.__('cURL Version').'</strong> '. $curl_version . ' | ';
		$footer_text .= '<strong>'.__('Wordpress Version').'</strong> '. get_bloginfo( 'version' ) . ' | ';
		$footer_text .= '<strong>'.__('WooCommerce Version').'</strong> '. WOOCOMMERCE_VERSION . ' | ';
		$footer_text .= '<strong>'.__('Last Updated').'</strong> '. $last_updated. ' | ';
		$footer_text .= '<a href="'.PaytmConstants::PLUGIN_DOC_URL.'" target="_blank">Developer Docs</a>';
		$footer_text .= '</div>';

		echo $footer_text;
	}

	/**
	 *  There are no payment fields for paytm, but we want to show the description if set.
	 **/
	public function payment_fields(){
		if($this->description) echo wpautop(wptexturize($this->description));
	}


	/**
	 * Receipt Page
	 **/
	public function receipt_page($order){
		echo $this->generate_paytm_form($order);
	}
	public function getOrderInfo($order)
	{
		if (version_compare(WOOCOMMERCE_VERSION, '2.7.0', '>='))
		{
			$data = array(
				'name'    	=> $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
				'email'   	=> $order->get_billing_email(),
				'contact' 	=> $order->get_billing_phone(),
				'amount'	=> $order->get_total(),
			);
		}
		else
		{
			$data = array(
				'name'		=> $order->billing_first_name . ' ' . $order->billing_last_name,
				'email'		=> $order->billing_email,
				'contact'	=> $order->billing_phone,
				'amount'	=> $order->order_total,
			);
		}

		return $data;
	}

	/**
	 * Generate paytm button link
	 **/
	public function generate_paytm_form($order_id){
		global $woocommerce;
		echo '<p>'.__(PaytmConstants::FRONT_MESSAGE, $this->id).'</p>';

		$transaction_url = PaytmHelper::getTransactionURL($this->getSetting('environment'));

		if(empty($transaction_url)){
			echo  '<div class="paytm_response box error-box">'. __(NOT_FOUND_TXN_URL) .'</div>';
			return;
		}

		if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
			$order = new WC_Order($order_id);
		} else {
			$order = new woocommerce_order($order_id);
		}
		
		$order_id = PaytmHelper::getPaytmOrderId($order_id);

		$getOrderInfo = $this->getOrderInfo($order);
	
		$mobile_no = $cust_id = $email = '';

		if(!empty($getOrderInfo['contact'])){
			$mobile_no = preg_replace('#[^0-9]{0,13}#is','',$getOrderInfo['contact']);	
		}
		
		if(!empty($getOrderInfo['email'])){
			$cust_id = $email = $getOrderInfo['email'];
		}else{
			$cust_id = "CUST_".$order_id;
		}

		$parameters = array(
				"MID"				=> $this->getSetting('merchant_id'),
				"CUST_ID"			=> sanitize_text_field($cust_id),
				"TXN_AMOUNT"		=> sanitize_text_field($getOrderInfo['amount']),
				"CALLBACK_URL" 		=> $this->getCallbackUrl(),
				"ORDER_ID"  		=> $order_id,
				"CHANNEL_ID" 		=> PaytmConstants::CHANNEL_ID,
				"INDUSTRY_TYPE_ID"	=> $this->getSetting('industry_type'),
				"WEBSITE"			=> $this->getSetting('website'),
				"EMAIL"				=> sanitize_email($email),
				"MOBILE_NO"			=> sanitize_text_field($mobile_no)
		);

		$parameters["CHECKSUMHASH"]		= PaytmChecksum::generateSignature($parameters, $this->getSetting('merchant_key'));

		$parameters["X-REQUEST-ID"] 	=  PaytmConstants::X_REQUEST_ID . WOOCOMMERCE_VERSION;

		$paytm_fields = "";
		foreach($parameters as $k => $v){
			$paytm_fields .= '<input type="hidden" name="'. $k .'" value="'. $v .'"/>';
		}

		return 
				'<form action="'.$transaction_url.'" method="POST" id="paytm_form_redirect">
					' . $paytm_fields . '
					<input type="submit" class="button-alt" id="submit_paytm_form_redirect" value="'.__(PaytmConstants::PAYTM_PAY_BUTTON, $this->id).'" />
					<a class="button cancel" href="'.$order->get_cancel_order_url().'">
						'.__(PaytmConstants::CANCEL_ORDER_BUTTON, $this->id).'
					</a>
					<script type="text/javascript">
					jQuery(function(){
						jQuery("body").block({
						message: "'.__(PaytmConstants::POPUP_LOADER_TEXT).'",
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
	


	/**
	 * Process the payment and return the result
	 **/
	public function process_payment($order_id)
	{
		if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
			$order = new WC_Order($order_id);
			} else {
			$order = new woocommerce_order($order_id);
		}
		
		if (version_compare(WOOCOMMERCE_VERSION, '3.0.0', '>='))
		{
			$order_key = $order->get_order_key();
		}else{
			$order_key = $order->order_key;
		}

		if (version_compare(WOOCOMMERCE_VERSION, '2.1', '>='))
		{
			return array(
				'result' => 'success',
				'redirect' => add_query_arg('key', $order_key, $order->get_checkout_payment_url(true))
			);
		}
		else if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>='))
		{
			return array(
				'result' => 'success',
				'redirect' => add_query_arg('order', $order->get_id(),
								add_query_arg('key', $order_key, $order->get_checkout_payment_url(true)))
			);
		}
		else
		{
			return array(
				'result' => 'success',
				'redirect' => add_query_arg('order', $order->get_id(),
								add_query_arg('key', $order_key, get_permalink(get_option('woocommerce_pay_page_id'))))
			);
		}
	}
			
	/**
	 * Check for valid paytm server callback // response processing //
	 **/
	public function check_paytm_response(){
		global $woocommerce;
		
		if(!empty($_POST['STATUS'])){
			
			if(!empty($_POST['CHECKSUMHASH'])){
				$post_checksum = $_POST['CHECKSUMHASH'];
				unset($_POST['CHECKSUMHASH']);	
			}else{
				$post_checksum = "";
			}
			$order = array();
			$isValidChecksum = PaytmChecksum::verifySignature($_POST, $this->getSetting('merchant_key'), $post_checksum);

			if($isValidChecksum === true)
			{
				$order_id = !empty($_POST['ORDERID'])? PaytmHelper::getOrderId($_POST['ORDERID']) : 0;

				/* save paytm response in db */
				if(PaytmConstants::SAVE_PAYTM_RESPONSE && !empty($_POST['STATUS'])){
					$order_data_id = saveTxnResponse($_POST, PaytmHelper::getOrderId($_POST['ORDERID']));
				}
				/* save paytm response in db */

				$responseDescription = (!empty($_POST['RESPMSG'])) ? $_POST['RESPMSG'] :"";

				if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
					$order = new WC_Order($order_id);
				} else {
					$order = new woocommerce_order($order_id);
				}

				if(!empty($order)){

						$reqParams = array(
									"MID" 		=> $this->getSetting('merchant_id'),
									"ORDERID" 	=> $_POST['ORDERID']
								);
				
						$reqParams['CHECKSUMHASH'] = PaytmChecksum::generateSignature($reqParams, $this->getSetting('merchant_key'));

						/* number of retries untill cURL gets success */
						$retry = 1;
						do{
							$resParams = PaytmHelper::executecUrl(PaytmHelper::getTransactionStatusURL($this->getSetting('environment')), $reqParams);
							$retry++;
						} while(!$resParams['STATUS'] && $retry < PaytmConstants::MAX_RETRY_COUNT);
						/* number of retries untill cURL gets success */

						if(!isset($resParams['STATUS'])){
							$resParams = $_POST;
						}

						/* save paytm response in db */
						if(PaytmConstants::SAVE_PAYTM_RESPONSE && !empty($resParams['STATUS'])){
							saveTxnResponse($resParams, PaytmHelper::getOrderId($resParams['ORDERID']), $order_data_id);
						}
						/* save paytm response in db */

						// if curl failed to fetch response
						if(!isset($resParams['STATUS'])){
							$this->fireFailure($order, __(PaytmConstants::ERROR_SERVER_COMMUNICATION));
						}else{
							if($resParams['STATUS'] == 'TXN_SUCCESS') {

								if($order->status !=='completed'){
								
									$this->msg['message']	= __(PaytmConstants::SUCCESS_ORDER_MESSAGE);
									$this->msg['class']		= 'success';
									
									if($order->status !== 'processing'){			
										$order->payment_complete();
										$order->reduce_order_stock();

										$message = "<br/>".sprintf(__(PaytmConstants::TRANSACTION_ID),$resParams['TXNID'])."<br/>".sprintf(__(PaytmConstants::PAYTM_ORDER_ID),$resParams['ORDERID']);

										$order->add_order_note($this->msg['message'] . $message);
										$woocommerce->cart->empty_cart();
									}
								}
							}else if($resParams['STATUS'] == 'PENDING'){
								$message = __(PaytmConstants::PENDING_ORDER_MESSAGE);
								if(!empty($responseDescription)){
									$message .= sprintf(__(PaytmConstants::REASON),$responseDescription);
								}
								$this->setStatusMessage($order, $message, 'on-hold');
							}else{
								$message = __(PaytmConstants::ERROR_ORDER_MESSAGE);
								if(!empty($responseDescription)){
									$message .= sprintf(__(PaytmConstants::REASON),$responseDescription);
								}
								$this->setStatusMessage($order, $message);
							}
						}
				}
				else{
						
					$this->setStatusMessage($order, __(PaytmConstants::ERROR_INVALID_ORDER));
				}

			}else{
				$this->setStatusMessage($order, __(PaytmConstants::ERROR_CHECKSUM_MISMATCH));
			}

			$redirect_url = $this->redirectUrl($order);

			$this->setMessages();
			
			//For wooCoomerce 2.0
			if($this->msg['class'] == 'error'){
				$redirect_url = add_query_arg(
					array(
						'paytm_response'	=> urlencode($this->msg['message']),
						'type'				=> $this->msg['class']
					), $redirect_url
				);
			}
			
			wp_redirect( $redirect_url );
			exit;
			}
	}
	/**
	* show template while response 
	*/
	private function setStatusMessage($order, $msg = '', $status = 'failed'){
		
		$this->msg['class'] = 'error';
		$this->msg['message'] = $msg;
		if(!empty($order)){
			$order->update_status($status);
			$order->add_order_note($this->msg['message']);
		}
	}

	private function setMessages(){
		global $woocommerce;
		if ( function_exists( 'wc_add_notice' ) ) {
			wc_add_notice( $msg['message'], $msg['class'] );
		} else {
			if( 'success' == $msg['class'] ) {
				$woocommerce->add_message( $msg['message']);
			}else{
				$woocommerce->add_error( $msg['message'] );

			}
			$woocommerce->set_messages();
		}	
	}

	private function redirectUrl($order){
		global $woocommerce;
		// Redirection after paytm payments response.
		if(!empty($order)){
			if('success' == $this->msg['class']) {
				$redirect_url = $order->get_checkout_order_received_url();
			}else{
				// $redirect_url = wc_get_checkout_url();
				$redirect_url = $order->get_view_order_url();
			}
		}else{
			$redirect_url = $woocommerce->cart->get_checkout_url();
		}
		return $redirect_url;		
	}

	
	/*
	* End paytm Essential Functions
	**/
}