const paytmsettings = window.wc.wcSettings.getSetting( 'paytm_data', {} ); 
const paytmlabel = window.wp.htmlEntities.decodeEntities( paytmsettings.title ) || window.wp.i18n.__( 'Paytm WooCommerce Payment Gateway', 'paytm' );
const invertLogo = window.wp.htmlEntities.decodeEntities( paytmsettings.invertLogo ) ;
src = "https://staticpg.paytmpayments.com/pg_plugins_logo/paytm_logo_paymodes.svg";
if(invertLogo==1){
    src = "https://staticpg.paytmpayments.com/pg_plugins_logo/paytm_logo_invert.svg";
}
const paytmContent = () => {
    return window.wp.htmlEntities.decodeEntities( paytmsettings.description || '' );
}; 
 const PaytmBlock_Gateway = {
    name: 'paytm',
    label: wp.element.createElement(
        'span', 
        null, 
        wp.element.createElement('img', { 
            src: src, 
            alt: paytmlabel,
        }),
        ''
    ),
    content: Object( window.wp.element.createElement )( paytmContent, null ),
    edit: Object( window.wp.element.createElement )( paytmContent, null ),
    canMakePayment: () => true,
    ariaLabel: paytmlabel,
     supports: {
        features: paytmsettings.supports,
    },
};
window.wc.wcBlocksRegistry.registerPaymentMethod( PaytmBlock_Gateway ); 