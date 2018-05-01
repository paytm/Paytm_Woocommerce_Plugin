# Woo-commerce Wordpress 4.x
  
  1. Upload the latest version of WooCommerce plugin installed.
  2. Copy the folder named 'paytm' into the directory location /wp-content/plugins/. Or, you may choose to upload the paytm folder via the Woocommerce Admin panel.
  3. Activate the plugin through the left side 'Plugins' menu in WordPress.
  4. Visit the Woo-Commerce => settings page, and click on the Checkout tab.
  5. Scroll down the Checkout page and go to the setting option of paytm under "Gateway Display".
  6. Click on paytm to edit the settings. If you do not see paytm in the list at the top of the screen make sure you have activated the plugin in the WordPress Plugin Manager.
  7. Save the below configuration
  8. Now you can see paytm in your payment option.
  9. Save the below configuration.

      * Enable                  - Enable check box
      * Title                   - Paytm
      * Description             - Default
      * Merchant Identifier     - Staging/Production MID provided by Paytm
      * Secret Key              - Staging/Production Key provided by Paytm
      * Website Name            - Provided by Paytm
      * Industry Type           - Provided by Paytm
      * Channel ID              - WEB/WAP
      * Gateway URL Staging     
        * Staging     - https://securegw-stage.paytm.in/theia/processTransaction
        * Production  - https://securegw.paytm.in/theia/processTransaction
      * Transaction Status URL  
        * Staging     - https://securegw-stage.paytm.in/merchant-status/getTxnStatus
        * Production  - https://securegw.paytm.in/merchant-status/getTxnStatus
      * Custom Callback Url     - Disable
      * Callback Url            - customized callback url(this is visible when Custom Callback Url is Enable)
      * Return Page             - My Account

  10. Your Woo-commerce plug-in is now installed. You can accept payment through Paytm.

# In case of any query, please contact to Paytm.