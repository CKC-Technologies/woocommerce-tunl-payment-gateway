jQuery(document).ready(function($){


    $(document).on("change keyup input","#tunl_ccno",function() { 
        jQuery('.card-input-img').remove();
        var getInputFirst = parseInt($(this).val().charAt(0));
        var imageSet = '';
        if( getInputFirst == 3 ){
            imageSet = '<img src="'+cardDetail.cardfolder+'/amex.svg" class="card-input-img">';
        }else if( getInputFirst == 4 ){
            imageSet = '<img src="'+cardDetail.cardfolder+'/visa.svg" class="card-input-img">';
        }else if( getInputFirst == 5 ){
            imageSet = '<img src="'+cardDetail.cardfolder+'/mastercard.svg" class="card-input-img">';
        }else if( getInputFirst == 6 ){
            imageSet = '<img src="'+cardDetail.cardfolder+'/discover.svg" class="card-input-img">';
        }
        jQuery('#tunl_ccno').after(imageSet);
    });

});







jQuery(window).load(function() {

    setTimeout(() => {

        jQuery('#tunl_ccno').mask('0000 0000 0000 0000');

        jQuery('#tunl_expdate').mask('00/00');

        jQuery('#tunl_cvc').mask('000');

    }, 4000);

});