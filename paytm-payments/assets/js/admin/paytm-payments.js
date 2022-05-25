(function() {


    if(window.location.host == "localhost"){
        //document.getElementById("woocommerce_paytm_iswebhook").disabled= true;
        document.getElementById('woocommerce_paytm_iswebhook').title = 'Webhook function will not work for localhost';
    }

    jQuery('#woocommerce_paytm_iswebhook').change(function() {
        jQuery('.webhookTrigger').text(1);

    });
    jQuery('.woocommerce-save-button').click(function(e) {
        var webhookTrigger = jQuery('.webhookTrigger').text();
        if(webhookTrigger ==1){
            var is_webhook = ''; 
            var environment  =jQuery('#woocommerce_paytm_environment').val();
            var mid  =jQuery('#woocommerce_paytm_merchant_id').val();   
            var webhookUrl  =jQuery('.webhook-url').text();
            
            
            jQuery('.webhook-message').html('');
            //if(this.checked) {
            if (jQuery('#woocommerce_paytm_iswebhook').is(':checked')) {
                is_webhook = 1;
            }else{
                is_webhook = 0;
            }
            if(mid==""){
                document.getElementById("woocommerce_paytm_iswebhook").checked = false;
                jQuery('.webhook-message').html('<div class="paytm_response error-box">Please enter MID</div>');
                return false;
            }
            if(webhookUrl==""){
                document.getElementById("woocommerce_paytm_iswebhook").checked = false;
                jQuery('.webhook-message').html('<div class="paytm_response error-box">Please check webhookUrl</div>');
                return false;
            }
         
            jQuery.ajax({
                type:"POST",
                dataType: 'json',
                data:{is_webhook:is_webhook,mid:mid,environment:environment,webhookUrl:webhookUrl},
                url: "admin-ajax.php?action=setPaymentNotificationUrl",
                async:false,
                success: function(data) {
                    if (data.message == true) {
                        //jQuery('.webhook-message').html('<div class="paytm_response success-box">WebhookUrl updated successfully</div>');
                        //alert("WebhookUrl updated successfully");
                    } else {
                        //document.getElementById("woocommerce_paytm_iswebhook").checked = false;
                        //jQuery('.webhook-message').html('<div class="paytm_response error-box">'+data.message+'</div>');
                    }

                    if(data.showMsg == true){
                        document.getElementById("woocommerce_paytm_iswebhook").checked = false;
                        alert(data.message);
                        window.open('https://dashboard.paytm.com/next/webhook-url', '_blank');
                    }
                },
                complete: function() { 
                    return true;
                 }
            });
        }

     });   

     jQuery('#woocommerce_paytm_enabled').click(function(){
        if (jQuery('#woocommerce_paytm_enabled').is(':checked')) {
            //do nothing
        }else{
            if (confirm('Are you sure you want to disable Paytm Payment Gateway, you will no longer be able to accept payments through us?')) {
                //disable pg
            }else{
                jQuery('#woocommerce_paytm_enabled').prop("checked",true);
            }    
        }
    });
 
})();
