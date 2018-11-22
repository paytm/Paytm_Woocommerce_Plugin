# Description

This plugin is used to integrate Paytm payment gateway with Woocommerce for Wordpress version 3.x.

# Installation
 1. Ensure you have latest version of WooCommerce plugin installed.
 2. Copy the folder named '*paytm*' into the directory location /wp-content/plugins/. Or, you may choose to upload the *paytm* folder via the Woocommerce Admin panel.
 3. Activate the plugin through the left side 'Plugins' menu in WordPress.


# Configuration

 1. Visit the WooCommerce => settings page, and click on the Checkout tab.
 2. Scroll down the Checkout page and go to the setting option of paytm under "Gateway Display".
 3. Click on paytm to edit the settings. If you do not see paytm in the list at the top of the screen make sure you have activated the plugin in the WordPress Plugin Manager.
 4. Enable the Payment Method, name it Credit Card / Debit Card / Internet Banking (this will show up on the payment page your customer sees), add in your merchant id , secret key, paytm Gateway URL, Industry Type, ChannelID, Web Site, Return  page,   mode(0 for trial purpose and 1 for live purpose) and  Click Save.
 5. Now you can see *paytm* in your payment option.

# Paytm PG URL Details
	* Staging	
		* Transaction URL             => https://securegw-stage.paytm.in/theia/processTransaction
		* Transaction Status Url      => https://securegw-stage.paytm.in/merchant-status/getTxnStatus

	* Production
		* Transaction URL             => https://securegw.paytm.in/theia/processTransaction
		* Transaction Status Url      => https://securegw.paytm.in/merchant-status/getTxnStatus

See Video : https://www.youtube.com/watch?v=cqdhF-9ApzE