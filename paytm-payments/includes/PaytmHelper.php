<?php
require_once(__DIR__.'/PaytmConstants.php');
if(!class_exists('PaytmHelper')) :
class PaytmHelper{

	/**
	* include timestap with order id
	*/
	public static function getPaytmOrderId($order_id){
		if($order_id && PaytmConstants::APPEND_TIMESTAMP){
			return $order_id . '_' . date("YmdHis");
		}else{
			return $order_id;
		}
	}
	/**
	* exclude timestap with order id
	*/
	public static function getOrderId($order_id){		
		if(($pos = strrpos($order_id, '_')) !== false && PaytmConstants::APPEND_TIMESTAMP) {
			$order_id = substr($order_id, 0, $pos);
		}
		return $order_id;
	}

	/**
	* exclude timestap with order id
	*/
	public static function getTransactionURL($isProduction = 0){		
		if($isProduction == 1){
			return PaytmConstants::TRANSACTION_URL_PRODUCTION;
		}else{
			return PaytmConstants::TRANSACTION_URL_STAGING;			
		}
	}
	/**
	* exclude timestap with order id
	*/
	public static function getTransactionStatusURL($isProduction = 0){		
		if($isProduction == 1){
			return PaytmConstants::TRANSACTION_STATUS_URL_PRODUCTION;
		}else{
			return PaytmConstants::TRANSACTION_STATUS_URL_STAGING;			
		}
	}
	/**
	* check and test cURL is working or able to communicate properly with paytm
	*/
	public static function validateCurl($transaction_status_url = ''){		
		if(!empty($transaction_status_url) && function_exists("curl_init")){
			$ch 	= curl_init(trim($transaction_status_url));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
			$res 	= curl_exec($ch);
			curl_close($ch);
			return $res !== false;
		}
		return false;
	}

	public static function getcURLversion(){		
		if(function_exists('curl_version')){
			$curl_version = curl_version();
			if(!empty($curl_version['version'])){
				return $curl_version['version'];
			}
		}
		return false;
	}

	public static function executecUrl($apiURL, $requestParamList) {

        $jsonResponse = wp_remote_post($apiURL, array(
            'headers'     => array(),
            'body'        => json_encode($requestParamList),
        ));

        //$response_code = wp_remote_retrieve_response_code( $jsonResponse );
        $response_body = wp_remote_retrieve_body( $jsonResponse );
        $responseParamList = json_decode($response_body, true);
        return $responseParamList;
    }

}
endif;
?>