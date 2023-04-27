(function() { 
    if (jQuery(".msg-by-paytm")[0]){
        document.getElementsByClassName('msg-by-paytm')[0].innerHTML = '';
    }
        jQuery(document).ready(function () {
            jQuery( 'body' ).on( 'updated_checkout', function() {
				let str = jQuery("label[for=payment_method_paytm]").html(); 
				/* let res = str.replace(/Paytm/, "");
				 jQuery("label[for=payment_method_paytm]").html(res); */
				jQuery("label[for=payment_method_paytm]").css("visibility","visible");
            });
            
        });
})();
 