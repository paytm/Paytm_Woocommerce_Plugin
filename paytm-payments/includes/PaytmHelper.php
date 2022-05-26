<?php
require_once(__DIR__.'/PaytmConstants.php');
if(!class_exists('PaytmHelper')) :
class PaytmHelper{

	/**
	* include timestap with order id
	*/
	public static function getPaytmOrderId($order_id){
		if($order_id && PaytmConstants::APPEND_TIMESTAMP){
			return PaytmConstants::ORDER_PREFIX.$order_id . '_' . date("YmdHis");
		}else{
			return PaytmConstants::ORDER_PREFIX.$order_id;
		}
	}
	/**
	* exclude timestap with order id
	*/
	public static function getOrderId($order_id){		
		if(($pos = strrpos($order_id, '_')) !== false && PaytmConstants::APPEND_TIMESTAMP) {
			$order_id = substr($order_id, 0, $pos);
		}
		if (substr($order_id, 0, strlen(PaytmConstants::ORDER_PREFIX)) == PaytmConstants::ORDER_PREFIX) {
			$order_id = substr($order_id, strlen(PaytmConstants::ORDER_PREFIX));
		} 
		return $order_id;
	}

	public static function getPaytmURL($url = false, $isProduction = 0){
		if(!$url) return false; 
		if($isProduction == 1){
			return PaytmConstants::PRODUCTION_HOST . $url;
		}else{
			return PaytmConstants::STAGING_HOST . $url;			
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
            'headers'     => array("Content-Type"=> "application/json"),
            'body'        => json_encode($requestParamList, JSON_UNESCAPED_SLASHES),
        ));

        //$response_code = wp_remote_retrieve_response_code( $jsonResponse );
        $response_body = wp_remote_retrieve_body( $jsonResponse );
        $responseParamList = json_decode($response_body, true);
        $responseParamList['request'] = $requestParamList;
        return $responseParamList;
    }

    public static function createJWTToken($key,$clientId,$environment){
		
		// Create token header as a JSON string
		$header = json_encode(['alg' => 'HS512','typ' => 'JWT']);

		// Create token payload as a JSON string
		//$time = time()- (1* 60);
		date_default_timezone_set("Asia/Kolkata");	
		// if($environment == 0){
		// 	$time = time()- (33);
		// }else{
		// 	$time = time();
		// }
		$time = time();
		$payload = json_encode(['client-id' => $clientId,'iat'=>$time]);

		// Encode Header to Base64Url String
		$base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));

		// Encode Payload to Base64Url String
		$base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

		// Create Signature Hash
		$signature = hash_hmac('SHA512', $base64UrlHeader . "." . $base64UrlPayload, $key, true);

		// Encode Signature to Base64Url String
		$base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

		// Create JWT
		$jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

		return $jwt;
	}

}
endif;
?>