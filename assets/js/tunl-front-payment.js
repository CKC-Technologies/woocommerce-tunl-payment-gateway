jQuery(document).ready(function($){

    let currentVal = 0;
    $(document).on("change input","#tunl_ccno",function() { 
        
        var getInputFirst = parseInt($(this).val().charAt(0));
        if (getInputFirst === currentVal) return;
        jQuery('.card-input-img').remove();
        currentVal = getInputFirst;
        var imageSet = '';
        if( getInputFirst == 3 ){
            imageSet = '<img src="'+cardDetail.cardfolder+'/amex.svg" class="card-input-img">';
            jQuery('#tunl_cvc').val('');
            jQuery('#tunl_expdate').val('')
            jQuery('#tunl_cvc').unmask().mask("0000"); 
            jQuery('#tunl_ccno').mask('0000 000000 00000');
        }else if( getInputFirst == 4 ){
            imageSet = '<img src="'+cardDetail.cardfolder+'/visa.svg" class="card-input-img">';
            jQuery('#tunl_cvc').val('');
            jQuery('#tunl_expdate').val('')
            jQuery('#tunl_cvc').unmask().mask("000"); 
            jQuery('#tunl_ccno').mask('0000 0000 0000 0000');
        }else if( getInputFirst == 5 ){
            imageSet = '<img src="'+cardDetail.cardfolder+'/mastercard.svg" class="card-input-img">';
            jQuery('#tunl_cvc').val('');
            jQuery('#tunl_expdate').val('')
            jQuery('#tunl_cvc').unmask().mask("000"); 
            jQuery('#tunl_ccno').mask('0000 0000 0000 0000');
        }else if( getInputFirst == 6 ){
            imageSet = '<img src="'+cardDetail.cardfolder+'/discover.svg" class="card-input-img">';
            jQuery('#tunl_cvc').val('');
            jQuery('#tunl_expdate').val('')
            jQuery('#tunl_cvc').unmask().mask("000"); 
            jQuery('#tunl_ccno').mask('0000 0000 0000 0000');
        }
        jQuery('#tunl_ccno').after(imageSet);
    });

    let mask_initialized = false;

    $(document).on("focus","#tunl_ccno, #tunl_expdate, #tunl_cvc",function() { 
        if (mask_initialized) return
        mask_initialized = true
        jQuery('#tunl_ccno').mask('0000 0000 0000 0000');
        jQuery('#tunl_expdate').mask('00/00');
        jQuery('#tunl_cvc').mask('000');
    });

});