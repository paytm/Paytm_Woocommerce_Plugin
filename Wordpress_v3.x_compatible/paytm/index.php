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
        public function __construct(){  // construct form //
            // Go wild in here
            $this -> id = 'paytm';
            $this -> method_title = __('paytm');
            $this -> icon = WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/images/logo.gif';
            $this -> has_fields = false;
            $this -> init_form_fields();
            $this -> init_settings();
            $this -> title = $this -> settings['title'];
            $this -> description = $this -> settings['description'];
            $this -> merchantIdentifier = $this -> settings['merchantIdentifier'];
            $this -> secret_key = html_entity_decode($this -> settings['secret_key']);            
			$this -> gateway_url = $this -> settings['gateway_url'];
			$this -> transaction_status_url = $this -> settings['transaction_status_url'];
			$this -> industry_type = $this -> settings['industry_type'];
			$this -> channel_id = $this -> settings['channel_id'];
			$this -> website = $this -> settings['website'];
            $this -> redirect_page_id = $this -> settings['redirect_page_id'];
			// $this -> mode = $this -> settings['mode'];
			$this -> callbackurl = $this -> settings['callbackurl'];
			$this -> log = $this -> settings['log'];
			//$this -> liveurl = "https:/api.paytm.com/transact?v=2";
            $this -> msg['message'] = "";
            $this -> msg['class'] = "";	
			
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
            add_action('woocommerce_thankyou_paytm',array(&$this, 'thankyou_page'));
        }
        //$merchant_id -> $gateway_url =>  $industry_type => $channel_id = >$website 

		function init_form_fields(){   

            $this -> form_fields = array(
                'enabled' => array(
                    'title' => __('Enable/Disable'),
                    'type' => 'checkbox',
                    'label' => __('Enable paytm Payment Module.'),
                    'default' => 'no'),
                'title' => array(
                    'title' => __('Title:'),
                    'type'=> 'text',
                    'description' => __('This controls the title which the user sees during checkout.'),
                    'default' => __('paytm')),
                'description' => array(
                    'title' => __('Description:'),
                    'type' => 'textarea',
                    'description' => __('This controls the description which the user sees during checkout.'),
                    'default' => __('The best payment gateway provider in India for e-payment through credit card, debit card & netbanking.')),

                'merchantIdentifier' => array(
                    'title' => __('Merchant Identifier'),
                    'type' => 'text',
                    'description' => __('This id(USER ID) available at "Generate Secret Key" of "Integration -> Card payments integration at paytm."')),
                
				'secret_key' => array(
                    'title' => __('Secret Key'),
                    'type' => 'text',
                    'description' =>  __('Given to Merchant by paytm'),
					),
				'callbackurl' => array(
				'title' => __('Set CallBack URL'),
				'type' => 'checkbox',
				'label' => __('Enable Call back URL.'),
				'default' => 'yes'),
					
                'gateway_url' => array(
                    'title' => __('Gateway URL'),
                    'type' => 'text',
                    'description' =>  __('Given to Merchant by paytm'),
					),
                'transaction_status_url' => array(
                    'title' => __('Transaction Status Url'),
                    'type' => 'text',
                    'description' =>__('Given to Merchant by paytm')
                ),
				'industry_type' => array(
                    'title' => __('Industry Type'),
                    'type' => 'text',
                    'description' =>  __('Given to Merchant by paytm'),
					),
				'channel_id' => array(
                    'title' => __('Channel ID'),
                    'type' => 'text',
                    'description' =>  __('WEB - for desktop websites / WAP - for mobile websites'),
					),
				'website' => array(
                    'title' => __('Web Site'),
                    'type' => 'text',
                    'description' =>  __('Given to Merchant by paytm'),
					),
				'redirect_page_id' => array(
                    'title' => __('Return Page'),
                    'type' => 'select',
                    'options' => $this -> get_pages('Select Page'),
                    'description' => "URL of success page"
                ),
				/*'mode' => array(
                    'title' => __('Mode'),
                    'type' => 'text',
                    'options' => 'text',
                    'description' => "Mode of transaction. (1=LIVE, 0=TEST)"
                ),*/
				'log' => array(
                    'title' => __('Do you want to log'),
                    'type' => 'text',
                    'options' => 'text',
                    'description' => "(yes/no)"
                )
            );


        }
		
		
        /**
         * Admin Panel Options
         * - Options for bits like 'title' and availability on a country-by-country basis
         **/
        public function admin_options(){
            echo '<h3>'.__('paytm Payment Gateway').'</h3>';
            echo '<p>'.__('India online payment solutions for all your transactions by paytm').'</p>';
            echo '<table class="form-table">';
            $this -> generate_settings_html();
            echo '</table>';

        }
        /**
         *  There are no payment fields for paytm, but we want to show the description if set.
         **/
        function payment_fields(){
            if($this -> description) echo wpautop(wptexturize($this -> description));
        }
        /**
         * Receipt Page
         **/
        function receipt_page($order){
            echo '<p>'.__('Thank you for your order, please click the button below to pay with paytm.').'</p>';
            echo $this -> generate_paytm_form($order);
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
				if(isset($_POST['ORDERID']) && isset($_POST['RESPCODE'])){
			    $order_sent = $_POST['ORDERID'];
			    $responseDescription = $_POST['RESPMSG'];
				if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
                $order = new WC_Order($_POST['ORDERID']);
				} else {
					$order = new woocommerce_order($_POST['ORDERID']);
				}
				if($this -> log == "yes"){error_log("Response Code = " . $_POST['RESPCODE']);}
			    $redirect_url = ($this -> redirect_page_id=="" || $this -> redirect_page_id==0)?get_site_url() . "/":get_permalink($this -> redirect_page_id);
				$this -> msg['class'] = 'error';
                $this -> msg['message'] = "Thank you for shopping with us. However, the transaction has been Failed For Reason  : " . $responseDescription;
				if($_POST['RESPCODE'] == 01) { // success
					
					$order_amount = $order -> order_total;
					
					if(($_POST['TXNAMOUNT']	== $order_amount)){
						if($this -> log == "yes"){error_log("amount matched");}
						// code by paytm team
						$order_sent			      = $_POST['ORDERID'];
						$res_code				  = $_POST['RESPCODE'];
						$responseDescription      = $_POST['RESPMSG'];
						$checksum_recv			  = $_POST['CHECKSUMHASH'];
						$paramList			      = $_POST;
						$order_amount = $_POST['TXNAMOUNT'];
						//  code by paytm team
						$all = $paramList;
						if($this -> log == "yes"){error_log("received parameters = " . $all);}
						$bool = "FALSE";
				        $bool = verifychecksum_e($paramList, $this -> secret_key, $checksum_recv);
						//$newcheck = Checksum::calculateChecksum($this->secret_key, $all);
						if($this -> log == "yes"){error_log("calculated checksum = " . $newch . " and checksum received = " . $_POST['CHECKSUMHASH']);}
                        if ($bool == "TRUE") {
							// Create an array having all required parameters for status query.
							$requestParamList = array("MID" => $this -> merchantIdentifier , "ORDERID" => $order_sent);
							
							$StatusCheckSum = getChecksumFromArray($requestParamList, $this->secret_key);
							
							$requestParamList['CHECKSUMHASH'] = $StatusCheckSum;
							
							// Call the PG's getTxnStatus() function for verifying the transaction status.
							/*	19751/17Jan2018	*/
								/*if($this -> mode==0)
								{
									$check_status_url = 'https://pguat.paytm.com/oltp/HANDLER_INTERNAL/getTxnStatus';
								}
								else
								{
									$check_status_url = 'https://secure.paytm.in/oltp/HANDLER_INTERNAL/getTxnStatus';
								}*/

								/*if($this -> mode==0) {
									$check_status_url = 'https://securegw-stage.paytm.in/merchant-status/getTxnStatus';
								} else {
									$check_status_url = 'https://securegw.paytm.in/merchant-status/getTxnStatus';
								}*/
								$check_status_url = $this->transaction_status_url;
							/*	19751/17Jan2018 end	*/
							
							$responseParamList = callNewAPI($check_status_url, $requestParamList);
							if($responseParamList['STATUS']=='TXN_SUCCESS' && $responseParamList['TXNAMOUNT']==$order_amount)
							{
								if($order -> status !=='completed'){
									error_log("SUCCESS");
									$this -> msg['message'] = "Thank you for your order . Your transaction has been successful.";
									$this -> msg['class'] = 'success';
									if($order -> status == 'processing'){

									} else {
										$order -> payment_complete();
										$order -> add_order_note('Mobile Wallet payment successful');
										$order -> add_order_note($this->msg['message']);
										$woocommerce -> cart -> empty_cart();

									}
								}
							}
							else
							{
								$this -> msg['class'] = 'error';
								$this -> msg['message'] = "It seems some issue in server to server communication. Kindly connect with administrator.";
								$order -> update_status('failed');
								$order -> add_order_note('Failed');
								$order -> add_order_note($this->msg['message']);
							}
						}
						else{
							// server to server failed while call//
							//error_log("api process failed");	
							$this -> msg['class'] = 'error';
							$this -> msg['message'] = "Severe Error Occur.";
							$order -> update_status('failed');
							$order -> add_order_note('Failed');
							$order -> add_order_note($this->msg['message']);
						}
						
					}
                    else{
						// Order mismatch occur //
						//error_log("order mismatch");	
						$this -> msg['class'] = 'error';
						$this -> msg['message'] = "Order Mismatch Occur";
						$order -> update_status('failed');
						$order -> add_order_note('Failed');
						$order -> add_order_note($this->msg['message']);
						
					}					
				}
				else{
					$order -> update_status('failed');
					$order -> add_order_note('Failed');
					$order -> add_order_note($responseDescription);
					$order -> add_order_note($this->msg['message']);
				
				}
				add_action('the_content', array(&$this, 'paytmShowMessage'));
				
				$redirect_url = ($this -> redirect_page_id=="" || $this -> redirect_page_id==0)?get_site_url() . "/":get_permalink($this -> redirect_page_id);
                //For wooCoomerce 2.0
                $redirect_url = add_query_arg( array('msg'=> urlencode($this -> msg['message']), 'type'=>$this -> msg['class']), $redirect_url );

                wp_redirect( $redirect_url );
                exit;		
			} 
		}
		
		
		/**
         * Generate paytm button link
         **/
        public function generate_paytm_form($order_id){
            global $woocommerce;
			$txnDate=date('Y-m-d');			
			$milliseconds = (int) (1000 * (strtotime(date('Y-m-d'))));
			if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
                $order = new WC_Order($order_id);
             } else {
                $order = new woocommerce_order($order_id);
            }
            $redirect_url = ($this -> redirect_page_id=="" || $this -> redirect_page_id==0)?get_site_url() . "/":get_permalink($this -> redirect_page_id);
			// pretty url check //
			$a = strstr($redirect_url,"?");
			if($a){ $redirect_url .= "&wc-api=WC_paytm";}
			else {$redirect_url .= "?wc-api=WC_paytm";}			
			error_log("redirect url = this {$redirect_url}");
			//////////////
            $order_id = $order_id;
			$amt=	$order -> order_total;
			$txntype='1';
			$ptmoption='1';
			$currency="INR";
			$purpose="1";
			$productDescription='paytm';
			$ip=$_SERVER['REMOTE_ADDR'];
			
			/*$post_variables = Array(
			"merchantIdentifier" => $this -> merchantIdentifier,
			"orderId" => $order_id,
			"returnUrl" => $redirect_url,
			"buyerEmail" => $order -> billing_email,
			"buyerFirstName" => $order -> billing_first_name,
			"buyerLastName" => $order -> billing_last_name,
			"buyerAddress" => $order -> billing_address_1,
			"buyerCity" => $order -> billing_city,
			"buyerState" => $order -> billing_state,
			"buyerCountry" => $order -> billing_country,
			"buyerPincode" => $order -> billing_postcode,
			"buyerPhoneNumber" => $order -> billing_phone,
			"txnType" => $txntype,
			"ptmoption" => $ptmoption,
			"mode" => $this -> mode,
			"currency" => $currency,
			"amount" => $amt, //Amount should be in paisa
			"merchantIpAddress" => $ip,
			"purpose" => $purpose,
			"productDescription" => $productDescription,
			"txnDate" => $txnDate

			);*/
			
			$email = '';
			$mobile_no = '';
			
			try{
				$email = $order -> billing_email;
			}catch(Exception $e){
			
			}
			
			try{
				$mobile_no = preg_replace('#[^0-9]{0,13}#is','',$order -> billing_phone);
			}catch(Exception $e){
			
			}
			
			$post_variables = Array(
            "MID" => $this -> merchantIdentifier,
            "ORDER_ID" => $order_id,
            "CUST_ID" => $email,
            "TXN_AMOUNT" => $amt,
            "CHANNEL_ID" => $this -> channel_id,
            "INDUSTRY_TYPE_ID" => $this -> industry_type,
            "WEBSITE" => $this -> website,
			"EMAIL" => $email,
			"MOBILE_NO" => $mobile_no
            );
			if($this -> callbackurl=='yes')
				{
					$post_variables["CALLBACK_URL"] = get_site_url() . '/?page_id=7&wc-api=WC_paytm';
				}
			$all = '';
			foreach($post_variables as $name => $value) {
			if($name != 'checksum') {
			$all .= "'";
			if ($name == 'returnUrl') {
			$all .= $value;
			} else {

			$all .= $value;
			}
			$all .= "'";
			}
			}
			if($this->log == "yes")
			{			
				error_log("AllParams : ".$all);
				error_log("Secret Key : ". $this -> secret_key);
			}
			//$checksum = Checksum::calculateChecksum($this->secret_key, $all);
			
			$checksum = getChecksumFromArray($post_variables, $this->secret_key);
			
            $paytm_args = array(
               'merchantIdentifier' => $this -> merchantIdentifier,
                'orderId' => $order_id,
		        'returnUrl' => $redirect_url,
				'buyerEmail' => $order -> billing_email,
				'buyerFirstName' => $order -> billing_first_name,
				'buyerLastName' => $order -> billing_last_name,
				'buyerAddress' => $order -> billing_address_1,
				'buyerCity' => $order -> billing_city,
				'buyerState' => $order -> billing_state,
				'buyerCountry' => $order -> billing_country,
				'buyerPincode' => $order -> billing_postcode,
				'buyerPhoneNumber' => $order -> billing_phone,
				'txnType' => $txntype,
				'ptmoption' => $ptmoption,
				// 'mode' => $this -> mode,
				'currency' => $currency,
				'amount' => $amt,
				'merchantIpAddress' => $ip,
				'purpose' => $purpose,
				'productDescription' => $productDescription,
				'txnDate' =>  $txnDate,
                'checksum' => $checksum
				);
				foreach($paytm_args as $name => $value) {
			if($name != 'checksum') {
			if ($name == 'returnUrl') {
			$value = $value;
			
			} else {
			$value = $value;
			
			}
			}
			}

			
			
            $paytm_args_array = array();
           /* foreach($paytm_args as $key => $value){
						if($key != 'checksum') {
			if ($key == 'returnUrl') {
                $paytm_args_array[] = "<input type='hidden' name='$key' value='". $value ."'/>";
				} else {
				$paytm_args_array[] = "<input type='hidden' name='$key' value='". $value ."'/>";
				}
				} else {
				$paytm_args_array[] = "<input type='hidden' name='$key' value='$value'/>";
				}
            }*/
			$paytm_args_array[] = "<input type='hidden' name='MID' value='".  $this -> merchantIdentifier ."'/>";
			$paytm_args_array[] = "<input type='hidden' name='ORDER_ID' value='". $order_id ."'/>";
			$paytm_args_array[] = "<input type='hidden' name='WEBSITE' value='". $this -> website ."'/>";
			$paytm_args_array[] = "<input type='hidden' name='INDUSTRY_TYPE_ID' value='". $this -> industry_type ."'/>";
			$paytm_args_array[] = "<input type='hidden' name='CHANNEL_ID' value='". $this -> channel_id ."'/>";
			$paytm_args_array[] = "<input type='hidden' name='TXN_AMOUNT' value='". $amt ."'/>";
			$paytm_args_array[] = "<input type='hidden' name='CUST_ID' value='". $email ."'/>";
			$paytm_args_array[] = "<input type='hidden' name='EMAIL' value='". $email ."'/>";
			$paytm_args_array[] = "<input type='hidden' name='MOBILE_NO' value='". $mobile_no ."'/>";
			
			if($this -> callbackurl=='yes')
				{
					$call = get_site_url() . '/?page_id=7&wc-api=WC_paytm';
					$paytm_args_array[] = "<input type='hidden' name='CALLBACK_URL' value='" . $call . "'/>";
				}
			
			$paytm_args_array[] = "<input type='hidden' name='txnDate' value='". date('Y-m-d H:i:s') ."'/>";
			$paytm_args_array[] = "<input type='hidden' name='CHECKSUMHASH' value='". $checksum ."'/>";

            return '<form action="'.$this -> gateway_url.'" method="post" id="paytm_payment_form">
                ' . implode('', $paytm_args_array) . '
                <input type="submit" class="button-alt" id="submit_paytm_payment_form" value="'.__('Pay via paytm').'" /> <a class="button cancel" href="'.$order->get_cancel_order_url().'">'.__('Cancel order &amp; restore cart').'</a>
                <script type="text/javascript">
jQuery(function(){
    jQuery("body").block(
            {
                message: "<img src=\"'.$woocommerce->plugin_url().'/assets/images/ajax-loader.gif\" alt=\"Redirecting…\" style=\"float:left; margin-right: 10px;\" />'.__('Thank you for your order. We are now redirecting you to paytm to make payment.').'",
                    overlayCSS:
            {
                background: "#fff",
                    opacity: 0.6
        },
        css: {
            padding:        20,
                textAlign:      "center",
                color:          "#555",
                border:         "3px solid #aaa",
                backgroundColor:"#fff",
                cursor:         "wait",
                lineHeight:"32px"
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
