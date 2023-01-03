jQuery(document).ready(function(){

});



jQuery(window).load(function() {
    console.log('testingggggg');
    setTimeout(() => {

        console.log(jQuery('#tunl_ccno').length);

        jQuery('#tunl_ccno').mask('0000 0000 0000 0000');

        jQuery('#tunl_expdate').mask('00/00');

        jQuery('#tunl_cvc').mask('000');

    }, 4000);

    

});