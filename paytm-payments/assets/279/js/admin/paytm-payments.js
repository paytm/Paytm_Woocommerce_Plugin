(function() {


    if(window.location.host == "localhost"){
        //document.getElementById("woocommerce_paytm_iswebhook").disabled= true;
        document.getElementById('woocommerce_paytm_iswebhook').title = 'Webhook function will not work for localhost';
    }
    var websiteName = jQuery("#woocommerce_paytm_otherWebsiteName").val();
    if(websiteName!=""){
        document.getElementById("woocommerce_paytm_otherWebsiteName").style.display = 'block';
    }
    jQuery('#woocommerce_paytm_iswebhook').change(function() {
        jQuery('.webhookTrigger').text(1);

    });
    jQuery('.woocommerce-save-button').click(function(e) {
        websiteNameValiation(false);
        var websiteName = jQuery('#woocommerce_paytm_website').val();
        if(websiteName == "OTHERS"){
            var otherWebsiiteName = jQuery("#woocommerce_paytm_otherWebsiteName").val();
            if(otherWebsiiteName == ""){
                websiteNameValiation(true);
            }
        }
        var webhookTrigger = jQuery('.webhookTrigger').text();
        if(webhookTrigger ==1){
            var is_webhook = ''; 
            var environment  =jQuery('#woocommerce_paytm_environment').val();
            var mid  =jQuery('#woocommerce_paytm_merchant_id').val();
            var mkey  =jQuery('#woocommerce_paytm_merchant_key').val();   
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
                data:{is_webhook:is_webhook,mid:mid,mkey:mkey,environment:environment,webhookUrl:webhookUrl},
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
                 },
                error: function (jqXHR, textStatus, errorThrown) {
                    alert('Internal error: ' + jqXHR.responseText);
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

    jQuery('#woocommerce_paytm_website').change(function() {
        websiteNameValiation(false);
        var data = jQuery('#woocommerce_paytm_website').val();
        if(data == "OTHERS"){
            document.getElementById("woocommerce_paytm_otherWebsiteName").style.display = 'block';
            document.getElementById("woocommerce_paytm_otherWebsiteName").setAttribute("placeholder", "Enter website name");


        }else{
            document.getElementById("woocommerce_paytm_otherWebsiteName").style.display = 'none';
            jQuery('#woocommerce_paytm_otherWebsiteName').val("");
        }
    });

    jQuery("#woocommerce_paytm_otherWebsiteName").on("keyup", function(event) {
        var value =jQuery("#woocommerce_paytm_otherWebsiteName").val();
        var check = isAlphaNumeric(value);
        
        if(!check){
            websiteNameValiation(true);
        }else{
           websiteNameValiation(false);

        }
    });
    
    function websiteNameValiation(showMessage = false){
        if(showMessage){
            //jQuery(".otherWebsiteName-error-message").text("Please enter a valid website name");
            jQuery(".otherWebsiteName-error-message").html("Please enter a valid website name provided by <a href='https://dashboard.paytm.com' target='_blank'>Paytm</a>");
            jQuery('.woocommerce-save-button').prop('disabled', true);
            document.getElementById('woocommerce_paytm_website').scrollIntoView(true);

        }else{
            jQuery(".otherWebsiteName-error-message").text("");
            jQuery('.woocommerce-save-button').prop('disabled', false);
        }
    }
    function isAlphaNumeric(str) {
      var code, i, len;
      for (i = 0, len = str.length; i < len; i++) {
        code = str.charCodeAt(i);
        if (!(code > 47 && code < 58) && // numeric (0-9)
            !(code > 64 && code < 91) && // upper alpha (A-Z)
            !(code > 96 && code < 123)) { // lower alpha (a-z)
          return false;
        }
      }
      return true;
    };

})();
