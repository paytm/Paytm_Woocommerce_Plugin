=== Paytm Payment Gateway === 
Contributors: integrationdevpaytm
Tags: Paytm, Paytm Payments, PayWithPaytm, Paytm WooCommerce, Paytm Plugin, Paytm Payment Gateway
Requires PHP: 5.6
Requires at least: 4.0.1
Tested up to: 5.8
Stable tag: 2.6.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

This is the official Paytm payment gateway plugin for WooCommerce. With this plugin you can enable India’s most widely-used checkout on your WooCommerce store. Get access to over 100+ payment sources and accept payments via Debit Card, Credit Card, Net Banking, UPI, International payments, EMI, Paytm wallet and Paytm Postpaid. This plugin enables hassle-free integration and works across all browsers and is compatible with the latest version of WooCommerce.

== Compatibilities and Dependencies ==

* Wordpress v3.9.2 or higher
* Woocommerce v2.4 or higher
* PHP v5.6.0 or higher
* Php-curl

== Features ==

* Unparalleled UX and success-rates leverage a vast user-base with 250mn+ saved cards and 100mn+ saved bank accounts 
* Wide range of payment sources - Let users choose from 100+ payment sources across - Cards, Net-banking, UPI, EMI, Paytm Wallet, Paytm Postpaid, Gift vouchers etc.
* Enable International payments acceptance with support for over 200+ countries
* Control the experience - Customize the payment page experience in line with your brand guidelines
* Most Robust UPI rails - Accept payments through any UPI app with Paytm Payments Bank’s in-house UPI gateways
* Settlements - Get complete control over your settlement schedule with options for real-time/on-demand settlements
* Instant Refunds - Trigger instant refunds straight to the user’s account through the Paytm dashboard
* Bank offers - Configure bank offers on cards/netbanking on the Paytm dashboad
* Checkout Analytics - Get rich data-insights on the Paytm dashboard


== Getting Started == 

Before enabling the Paytm Payment Gateway on Woocommerce, make sure you have a registered business account with Paytm. Please visit - 
[Paytm Dashboard](https://dashboard.paytm.com) to sign-up

== Step-1: Generate your API keys with Paytm ==

To generate the API Key,
* Log into your [Dashboard](https://dashboard.paytm.com/).
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
In case of any issues with integration, please [get in touch](https://business.paytm.com/contact-us#developer).

== Screenshots ==

1. Paytm vs Other Payment Gateways
2. Generate Your Unique Keys
3. WooCommerce Paytm-Configuration

== Installation ==

* Download Paytm Payment Gateway plugin 
* Upload all plugin files in "wp-content/plugins/" directory
* Install and activate the plugin from Wordpress admin panel
* Visit the WooCommerce > Settings page to configure Paytm Payment Gateway Plugin.
* Your Paytm Payment Gateway plugin is now setup. You can now accept payments through Paytm.
