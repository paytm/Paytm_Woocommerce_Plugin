<?php

class PaytmConstants{
	CONST TRANSACTION_STATUS_URL_PRODUCTION		= "https://securegw.paytm.in/order/status";
	CONST TRANSACTION_STATUS_URL_STAGING		= "https://securegw-stage.paytm.in/order/status";

	CONST PRODUCTION_HOST						= "https://securegw.paytm.in/";
	CONST STAGING_HOST							= "https://securegw-stage.paytm.in/";

	CONST ORDER_PROCESS_URL						= "order/process";
	CONST ORDER_STATUS_URL						= "order/status";
	CONST INITIATE_TRANSACTION_URL				= "theia/api/v1/initiateTransaction";
	CONST CHECKOUT_JS_URL						= "merchantpgpui/checkoutjs/merchants/MID.js";


	CONST SAVE_PAYTM_RESPONSE 					= true;
	CONST CHANNEL_ID							= "WEB";
	CONST APPEND_TIMESTAMP						= true;
	CONST ORDER_PREFIX							= "";
	CONST X_REQUEST_ID							= "PLUGIN_WOOCOMMERCE_";
	CONST PLUGIN_DOC_URL						= "https://developer.paytm.com/docs/woocommerce/";

	CONST MAX_RETRY_COUNT						= 3;
	CONST CONNECT_TIMEOUT						= 10;
	CONST TIMEOUT								= 10;

	CONST LAST_UPDATED							= "20220616";
	CONST PLUGIN_VERSION						= "2.7.0";
	CONST PLUGIN_VERSION_FOLDER					= "270";

	CONST CUSTOM_CALLBACK_URL					= "";


	CONST ID									= "paytm";
	CONST METHOD_TITLE							= "Paytm Payments";
	CONST METHOD_DESCRIPTION					= "The best payment gateway provider in India for e-payment through credit card, debit card & netbanking.";

	CONST TITLE									= "Paytm";
	CONST DESCRIPTION							= "The best payment gateway provider in India for e-payment through credit card, debit card & netbanking.";

	 
	
	CONST FRONT_MESSAGE						= "Thank you for your order, please click the button below to pay with paytm.";
	CONST NOT_FOUND_TXN_URL					= "Something went wrong. Kindly contact with us.";
	CONST PAYTM_PAY_BUTTON					= "Pay via Paytm";
	CONST CANCEL_ORDER_BUTTON				= "Cancel order & Restore cart";
	CONST POPUP_LOADER_TEXT					= "Thank you for your order. We are now redirecting you to paytm to make payment.";

	CONST TRANSACTION_ID					= "<b>Transaction ID:</b> %s";
	CONST PAYTM_ORDER_ID					= "<b>Paytm Order ID:</b> %s";

	CONST REASON							= " Reason: %s";
	CONST FETCH_BUTTON						= "Fetch Status";

	//Success
	CONST SUCCESS_ORDER_MESSAGE				= "Thank you for your order. Your payment has been successfully received.";
	CONST RESPONSE_SUCCESS					= "Updated <b>STATUS</b> has been fetched";
	CONST RESPONSE_STATUS_SUCCESS			= " and Transaction Status has been updated <b>PENDING</b> to <b>%s</b>";
	CONST RESPONSE_ERROR					= "Something went wrong. Please again'";

	//Error
	CONST PENDING_ORDER_MESSAGE				= "Your payment has been pending!";
	CONST ERROR_ORDER_MESSAGE				= "Your payment has been failed!";
	CONST ERROR_SERVER_COMMUNICATION		= "It seems some issue in server to server communication. Kindly connect with us.";
	CONST ERROR_CHECKSUM_MISMATCH			= "Security Error. Checksum Mismatched!";
	CONST ERROR_AMOUNT_MISMATCH				= "Security Error. Amount Mismatched!";
	CONST ERROR_INVALID_ORDER				= "No order found to process. Kindly contact with us.";
	CONST ERROR_CURL_DISABLED				= "cURL is not enabled properly. Please verify.";
	CONST ERROR_CURL_WARNING				= "Your server is unable to connect with us. Please contact to Paytm Support.";

 	CONST WEBHOOK_STAGING_URL		        = "https://boss-stage.paytm.in/";
	CONST WEBHOOK_STAGING_KEY               = 'DSqy6pGOhBR9CUhUFQygzlkG1+x66C6zV+M8bnGiwpEH+MvNsWmaJLirjxMVpRH+9c9XjZxse+wSYuNYmkG4uA==';
    CONST WEBHOOK_STAGING_CLIENTID          = '54b88dbc-fdd0-4f6f-af09-4ee76e90135a';
    
	CONST WEBHOOK_PRODUCTION_URL		    = "https://boss-ext.paytm.in/";
    CONST WEBHOOK_PRODUCTION_KEY            = 'jF5tDDCJ8/bRot8X5DAGVHiC+KwW9SQuckYya12NZ2/EWVBhU7Cj45A4lOSvo797uJ4M3LB5mTjiC0nhDYKZGg==';
    CONST WEBHOOK_PRODUCTION_CLIENTID       = 'f7484d06-f307-4e10-b661-0191f5efe031';


}

?>