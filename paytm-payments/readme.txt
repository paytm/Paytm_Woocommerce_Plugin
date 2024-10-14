=== Paytm Payment Gateway === 
Contributors: integrationdevpaytm
Tags: Paytm, Paytm Payments, PayWithPaytm, Paytm WooCommerce, Paytm Payment Gateway
Requires PHP: 7.4
Requires at least: 4.0.1
Tested up to: 6.5.5
Stable tag: 2.8.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

Welcome to the official Paytm Payment Gateway plugin for Woocommerce. Paytm Payment Gateway is ideal for Woocommerce and Wordpress merchants since it allows them to give their customers a seamless, super-fast checkout experience backed by cutting-edge payments technology that powers India’s largest payments platform. Accept payments from over 100+ payment sources including credit cards, debit cards, netbanking from 50+ banks (including HDFC & SBI), UPI, wallets and Buy-now-pay-later options. Here are a few reasons why Woocommerce merchants should choose Paytm Payment Gateway.  

== Compatibilities and Dependencies ==

* Wordpress v3.9.2 or higher
* Woocommerce v2.4 or higher
* PHP v7.4.0 or higher
* Php-curl

== Features ==

* Largest scale:  Preferred by 330M+ consumers in India.
* India’s most reliable PG: Trusted by India’s biggest online brands such as Uber, Flipkart, Zomato, Airtel, IRCTC, LIC and many more.  
* Industry best prices guaranteed: 2x more affordable than other payment gateways with 0% transaction fees on UPI & Rupay payments.
* Boost  conversions: Affordability options like EMI and Paytm Postpaid to boost conversions.
* Superior technology: Industry best success rates & 99.99% Up-time, Capable of supporting 3x more transactions per second than other payment gateways.
* Superfast next day settlements, even on holidays and weekends.
* Powerful dashboard: Get payment analytics at your fingerprints. Get insights by payment source and customer cohorts.
* Instant refunds: Initiate refunds seamlessly with just a click right from your Paytm for business dashboard. 

== Getting Started == 

New to PaytmPG? Use this [link](https://dashboard.paytmpayments.com) to create your Paytm for Business account and get access to exciting offers.

Before enabling the Paytm Payment Gateway on Woocommerce, make sure you have a registered business account with Paytm. Please visit - 
[Paytm Dashboard](https://dashboard.paytmpayments.com) to sign-up

== Step-1: Generate your API keys with Paytm ==

To generate the API Key,
* Log into your [Dashboard](https://dashboard.paytmpayments.com/).
* Select the API Keys under Developers on the left menu-bar.
* Select the mode for which you want to generate the API Key from the menu.
* Click Generate now to generate a key for the test mode and in case of live mode, first activate the account by submitting documents and then generate the key by clicking the Generate now button.
* You will get the merchant ID and merchant key in response to the above. Please make a note of these to be used further.

Note: You have to generate separate API Keys for the test and live modes. No money is deducted from your account in test mode.
MID and merchant keys generation may take few minutes. In case you do not see these details, please logout and login after 5 minutes. Proceed now to generate these keys.

== Step-2: Plugin Installation == 

There are 2 ways of installing the Paytm payment gateway plugin:-
i)  Download the plugin repository from <here> 
            OR
ii) Install the plugin directly from the Wordpress dashboard

Note: In case you have installed the plugin directly from the wordpress dashboard, skip to Step-3. In case you have downloaded the repository from here, follow the steps below to complete the installation.

== Steps after downloading the plugin ==

1. Unzip and open the downloaded folder.
2. Copy the Paytm-payments folder from the unzipped folder.
3. Paste it into /wp-content/plugins/ directory or you may choose to upload the Paytm folder via the Woocommerce Webstore Admin panel.

== Step-3: Configuration ==

* Log into your WordPress admin and activate the Paytm plugin in WordPress Plugin Manager.
* Log into your WooCommerce Webstore account, navigate to Settings and click the Checkout/Payment Gateways tab
* Scroll down to the Checkout page and go to the setting option of Paytm under Gateway Display
* Click on Paytm to edit the settings. If you do not see Paytm in the list at the top of the screen make sure you have activated the plugin in the WordPress Plugin Manager
* Fill in the following credentials.
	* Enable - Enable check box
	* Title - Paytm
	* Description - Default
	* Merchant Identifier - Staging/Production MID provided by Paytm
	* Secret Key - Staging/Production Key provided by Paytm
	* Website Name - Provided by Paytm
	* Industry Type - Provided by Paytm
	* Environment - Select environment type

Your Paytm payment gateway is enabled. Now you can accept payment through Paytm.
In case of any issues with integration, please [get in touch](https://www.paytmpayments.com/contact-us.html).

== Screenshots ==

1. Paytm vs Other Payment Gateways
2. Generate Your Unique Keys
3. WooCommerce Paytm-Configuration

== Installation ==

* Download Paytm Payment Gateway plugin 
* Upload all plugin files in "wp-content/plugins/" directory
* Install and activate the plugin from Wordpress admin panel
* Visit the WooCommerce > Settings page to configure Paytm Payment Gateway Plugin.
* Your Paytm Payment Gateway plugin is now setup. You can now accept payments through Paytmm.

== Changelog ==

= 2.8.6 =
* PPSL PG Redirection

= 2.8.5 =
* Webhook response handling improvment 
* PHP 8.3 support added.
* EMI subvention handling improvment

= 2.8.4 =
* Compatible and tested with WooCommerce version up to 9.0.2.
* Supports Checkout Block feature. 
* Enhanced security with updates.

= 2.8.0 =
* Compatible and tested with WooCommerce version up to 7.8.2.
* Supports HPOS WooCommerce feature.
* Enhanced security with updates.

= 2.7.9 =
* Compatible and tested with Woocommerce version upto 7.5.1
* Optimized JS and CSS
* Updated Security

= 2.7.7 =
* Compatible and tested with Woocommerce version upto 7.2.0
* Logo issue fixed
* Enabled title on checkout page

= 2.7.3 =
* Fixed security issues

= 2.7.0 =
* Stable release