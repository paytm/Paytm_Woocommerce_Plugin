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
		$this->icon 						= plugins_url('images/paytm_logo.png' , __FILE__);
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
		wp_enqueue_style('paytmadminWoopayment', plugin_dir_url( __FILE__ ) . 'assets/css/admin/paytm-payments.css', array(), '', '');
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
        $checkout_page_id = get_option('woocommerce_checkout_page_id');
        $checkout_page_id = (int) $checkout_page_id > 0 ? $checkout_page_id : 7;
        $webhookUrl = get_site_url() . '/?wc-api=WC_paytm&webhook=yes';
        $this->form_fields = array(
            'enabled'           => array(
                'title'             => __('Enable/Disable', $this->id),
                'type'          => 'checkbox',
                'label'         => __('Enable Paytm Payments.', $this->id),
                'default'       => 'no'
            ),
            'title' => array(
                'title'         => __('Title', $this->id),
                'type'          => 'text',
                'description'   => __('This controls the title which the user sees during checkout.', $this->id),
                'default'       => __(PaytmConstants::TITLE, $this->id),
            ),
            'description' => array(
                'title'         => __('Description', $this->id),
                'type'          => 'textarea',
                'description'   => __('This controls the description which the user sees during checkout.', $this->id),
                'default'       => __(PaytmConstants::DESCRIPTION, $this->id)
            ),
            'merchant_id'=> array(
                'title'         => __('Merchant ID'),
                'type'          => 'text',
                'custom_attributes' => array( 'required' => 'required' ),
                'description'   => __('Based on the selected Environment Mode, copy the relevant Merchant ID for test or production environment available on <a href="https://dashboard.paytm.com/next/apikeys" target="_blank">Paytm dashboard</a>.', $this->id),
            ),
            'merchant_key' => array(
                'title'         => __('Merchant Key'),
                'type'          => 'text',
                'custom_attributes' => array( 'required' => 'required' ),
                'description'   => __('Based on the selected Environment Mode, copy the Merchant Key for test or production environment available on <a href="https://dashboard.paytm.com/next/apikeys" target="_blank">Paytm dashboard</a>.', $this->id),
            ),
            'website' => array(
                'title'         => __('Website Name'),
                'type'          => 'text',
                'custom_attributes' => array( 'required' => 'required' ),
                'description'   => __('Enter "WEBSTAGING" for test/integration environment & "DEFAULT" for production environment.', $this->id),
            ),
            'industry_type' => array(
                'title'         => __('Industry Type'),
                'type'          => 'text',
                'custom_attributes' => array( 'required' => 'required' ),
                'description'   => __('Login to <a href="https://dashboard.paytm.com/next/apikeys" target="_blank">Paytm dashboard</a> & copy paste the industry type available there.', $this->id),
            ),
            'environment' => array(
                'title'         => __('Environment'), $this->id,
                'type'          => 'select',
                'custom_attributes' => array( 'required' => 'required' ),
                'options'       => array("0" => "Staging", "1" => "Production"),
                'description'   => __('Select "Staging" for test/integration environment & "Production" once you move to production environment.', $this->id),
                'default'       => '0'
            ),
            'iswebhook' => array(
                'title' => __('Enable Webhook', $this->id),
                'type' => 'checkbox',
                'description' =>  "<span style='color:#00b9f5'>$webhookUrl</span><br/><br/>Instructions and guide to <a href='https://developer.paytm.com/docs/payment-status/' target='_blank'>Paytm webhooks</a>",
                'label' => __('Enable Paytm Webhook <a href="https://dashboard.paytm.com/next/webhook-url" target="_blank">here</a> with the URL listed below.', $this->id),
                'default' => 'no'
            ),
            'emiSubvention' => array(
                'title'         => __('Enable EMI Subvention'), $this->id,
                'type'          => 'select',
                'custom_attributes' => array( 'required' => 'required' ),
                'options'       => array("0" => "No", "1" => "Yes"),
                'default'       => '0',
                'description' => 'Get your EMI Subvention plans configured at Paytm & then Select "Yes" to offer EMI Subvention to your customers.'
            ),
            'bankOffer' => array(
                'title'         => __('Enable Bank Offers'), $this->id,
                'type'          => 'select',
                'custom_attributes' => array( 'required' => 'required' ),
                'options'       => array("0" => "No", "1" => "Yes"),
                'default'       => '0',
                'description'=> 'Get your Bank Offer plans configured at Paytm & then Select "Yes" to provide Bank Offer to your customers.'
            ),
            'dcEmi' => array(
                'title'         => __('Enable DC EMI'), $this->id,
                'type'          => 'select',
                'custom_attributes' => array( 'required' => 'required' ),
                'options'       => array("0" => "No", "1" => "Yes"),
                'description'   => __('*For DC EMI Mobile Number Field is Mandatory.', $this->id),
                'default'       => '0',
                'description' => 'Get DC EMI enabled for your MID and then select "Yes" to offer DC EMI to your customer. Customer mobile number is mandatory for DC EMI.'
            ),
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
		if(!empty(PaytmHelper::getPaytmURL(PaytmConstants::ORDER_STATUS_URL, $this->getSetting('environment')))){
			$response = (array)wp_remote_get(PaytmHelper::getPaytmURL(PaytmConstants::ORDER_STATUS_URL, $this->getSetting('environment')));
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
	/* 
	* Get the transaction token
	*/
	public function blinkCheckoutSend($paramData = array())
	{
		$data=array();
		if(!empty($paramData['amount']) && (int)$paramData['amount'] > 0)
		{
			/* body parameters */
			$paytmParams["body"] = array(
				"requestType" => "Payment",
				"mid" => $this->getSetting('merchant_id'),
				"websiteName" => $this->getSetting('website'),
				"orderId" => $paramData['order_id'],
				"callbackUrl" => $this->getCallbackUrl(),
				"txnAmount" => array(
					"value" => $paramData['amount'],
					"currency" => "INR",
				),
				"userInfo" => array(
					"custId" => $paramData['cust_id'],
				),
			);
			// for bank offers
			if($this->getSetting('bankOffer') ==1){
				$paytmParams["body"]["simplifiedPaymentOffers"]["applyAvailablePromo"]= true;
			}
			// for emi subvention
			if($this->getSetting('emiSubvention') ==1){
				$paytmParams["body"]["simplifiedSubvention"]["customerId"]= $paramData['cust_id'];
				$paytmParams["body"]["simplifiedSubvention"]["subventionAmount"]= $paramData['amount'];
				$paytmParams["body"]["simplifiedSubvention"]["selectPlanOnCashierPage"]= true;
				$paytmParams["body"]["simplifiedSubvention"]["offerDetails"]["offerId"]= 1;
			}
			// for DC EMI
			if($this->getSetting('dcEmi') ==1){
				$paytmParams["body"]["userInfo"]["mobile"]= $paramData['cust_mob_no'];
				
			}
			$checksum = PaytmChecksum::generateSignature(json_encode($paytmParams["body"], JSON_UNESCAPED_SLASHES), $this->getSetting('merchant_key')); 
			
			$paytmParams["head"] = array(
				"signature"	=> $checksum
			);
			
			/* prepare JSON string for request */
			$post_data = json_encode($paytmParams, JSON_UNESCAPED_SLASHES);

			$url = PaytmHelper::getPaytmURL(PaytmConstants::INITIATE_TRANSACTION_URL, $this->getSetting('environment')) . '?mid='.$paytmParams["body"]["mid"].'&orderId='.$paytmParams["body"]["orderId"];
			
			$res= PaytmHelper::executecUrl($url, $paytmParams);
			
			if(!empty($res['body']['resultInfo']['resultStatus']) && $res['body']['resultInfo']['resultStatus'] == 'S'){
				$data['txnToken']= $res['body']['txnToken'];
			}
			else
			{
				$data['txnToken']="";
			}
			/* $txntoken = json_encode($res); */
		}
		return $data;
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
		
		$order_id = PaytmHelper::getPaytmOrderId($order_id);

		$getOrderInfo = $this->getOrderInfo($order);
		
		if(!empty($getOrderInfo['email'])){
			$cust_id = $email = $getOrderInfo['email'];
		}else{
			$cust_id = "CUST_".$order_id;
		}
		//get mobile no if there for DC_EMI
		if(isset($getOrderInfo['contact']) && !empty($getOrderInfo['contact'])){
			$cust_mob_no = $getOrderInfo['contact'];
		}else{
			$cust_mob_no = "";
		}
		$settings = get_option( "woocommerce_paytm_settings" );
		$checkout_url         = str_replace('MID',$settings['merchant_id'], PaytmHelper::getPaytmURL(PaytmConstants::CHECKOUT_JS_URL, $settings['environment']));
		   echo '';
		   
		$wait_msg='<script type="application/javascript" crossorigin="anonymous" src="'.$checkout_url.'" onload="invokeBlinkCheckoutPopup();"></script><div id="paytm-pg-spinner" class="paytm-woopg-loader"><div class="bounce1"></div><div class="bounce2"></div><div class="bounce3"></div><div class="bounce4"></div><div class="bounce5"></div><p class="loading-paytm">Loading Paytm...</p></div><div class="paytm-overlay paytm-woopg-loader"></div><div class="paytm-action-btn"><a href="" class="refresh-payment re-invoke">Pay Now</a><a href="'.wc_get_checkout_url().'" class="refresh-payment">Cancel</a></div>';
		$paramData = array('amount' => $getOrderInfo['amount'], 'order_id' => $order_id, 'cust_id' => $cust_id,'cust_mob_no' => $cust_mob_no);
		$data= $this->blinkCheckoutSend($paramData);
		
			return '<script type="text/javascript">
			function invokeBlinkCheckoutPopup(){
				console.log("method called");
				var config = {
					"root": "",
					"flow": "DEFAULT",
					"data": {
					  "orderId": "'.$order_id.'", 
					  "token": "'.$data['txnToken'].'", 
					  "tokenType": "TXN_TOKEN",
					  "amount": "'.$getOrderInfo['amount'].'"
					},
					"integration": {
						"platform": "Woocommerce",
						"version": "'.WOOCOMMERCE_VERSION.'|'.PAYTM_VERSION.'"
					},
					"handler": {
					  "notifyMerchant": function(eventName,data){
						console.log("notifyMerchant handler function called");
						if(eventName=="APP_CLOSED")
						{
							jQuery(".loading-paytm").hide();
							jQuery("#paytm-pg-spinner").hide();
							jQuery(".paytm-overlay").hide();
							jQuery(".refresh-payment").show();
						}
					  } 
					}
				  };
				  if(window.Paytm && window.Paytm.CheckoutJS){
					  window.Paytm.CheckoutJS.onLoad(function excecuteAfterCompleteLoad() {
						  window.Paytm.CheckoutJS.init(config).then(function onSuccess() {
							  window.Paytm.CheckoutJS.invoke();
						  }).catch(function onError(error){
							  console.log("error => ",error);
						  });
					  });
				  } 
			}
			jQuery(document).ready(function(){ jQuery(".re-invoke").on("click",function(){ window.Paytm.CheckoutJS.invoke(); return false; }); });
			</script>'.$wait_msg.'
			';
		
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

			//check order status before executing webhook call
			if (isset($_GET['webhook']) && $_GET['webhook'] =='yes') {
				$getOrderId = !empty($_POST['ORDERID'])? PaytmHelper::getOrderId($_POST['ORDERID']) : 0;
				if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
					$orderCheck = new WC_Order($getOrderId);
				} else {
					$orderCheck = new woocommerce_order($getOrderId);
				}

				if($orderCheck->status == "processing" || $orderCheck->status == "completed"){
					exit;
				}
			}
			//end webhook check
			
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
							
							$resParams = PaytmHelper::executecUrl(PaytmHelper::getPaytmURL(PaytmConstants::ORDER_STATUS_URL, $this->getSetting('environment')), $reqParams);
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
										$order->payment_complete($resParams['TXNID']);
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
								$this->setStatusMessage($order, $message, 'pending');
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

			$this->setMessages($this->msg['message'],$this->msg['class']);
			
			//For wooCoomerce 2.0
			/* if($this->msg['class'] == 'error'){
				$redirect_url = add_query_arg(
					array(
						'paytm_response'	=> urlencode($this->msg['message']),
						'type'				=> $this->msg['class']
					), $redirect_url
				);
			} */
			
			if (isset($_GET['webhook']) && $_GET['webhook'] =='yes') {
				 echo "Webhook Received";
			}else{
				wp_redirect( $redirect_url );
			}
			
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

	/* private function setMessages(){
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
	} */
	private function setMessages($message='',$class=''){
		global $woocommerce;
		if ( function_exists( 'wc_add_notice' ) ) {
			wc_add_notice( $message, $class );
		} else {
			if( 'success' == $class ) {
				$woocommerce->add_message( $message);
			}else{
				$woocommerce->add_error( $message );

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
				//$redirect_url = wc_get_checkout_url();
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
