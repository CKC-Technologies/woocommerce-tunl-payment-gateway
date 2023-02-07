jQuery(document).ready(function(){

	toastr.options = {

	  "closeButton": false,

	  "debug": false,

	  "newestOnTop": false,

	  "progressBar": false,

	  "positionClass": "toast-top-right",

	  "preventDuplicates": false,

	  "onclick": null,

	  "showDuration": "300",

	  "hideDuration": "1000",

	  "timeOut": "3000",

	  "extendedTimeOut": "1000",

	  "showEasing": "swing",

	  "hideEasing": "linear",

	  "showMethod": "fadeIn",

	  "hideMethod": "fadeOut"

	}



	jQuery('#mainform #woocommerce_tunl_password').attr('autocomplete','new-password');

	jQuery('#mainform #woocommerce_tunl_tunl_token').attr('readonly','readonly');

	jQuery('#mainform #woocommerce_tunl_tunl_token').parent().append('<a href="javascript:void(0)" class="btn-connected">Connected</a>');

	if( jQuery('#mainform #woocommerce_tunl_connect_button').val() == 1 ){

		jQuery('#mainform #woocommerce_tunl_connect_button').parents('.forminp').append('<div class="connect-btn-section"><img src="'+adminAjax.ajaxloader+'" class="loader-connect-class"><a href="javascript:void(0)" class="btn button-primary btn-connect-payment">Authenticate</a></div>');

	}else{

		jQuery('#mainform #woocommerce_tunl_connect_button').parents('.forminp').parent().hide();
		// jQuery('#mainform #woocommerce_tunl_connect_button').parents('.forminp').append('<div class="disconnect-btn-section"><img src="'+adminAjax.ajaxloader+'" class="loader-disconnect-class"><a href="javascript:void(0)" class="btn button-primary btn-disconnect-payment">Disconnect To Tunl</a></div>');

	}



	jQuery(document).on('click','.btn-connect-payment',function(){

		if( jQuery('#woocommerce_tunl_username').val() == '' ){

			toastr["error"]("Please enter first username!");

		}else if( jQuery('#woocommerce_tunl_password').val() == '' ){

			toastr["error"]("Please enter first password!");

		}else{

			jQuery('.loader-connect-class').show();

			jQuery('.btn-connect-payment').css('pointer-events','none');

			jQuery('.btn-connect-payment').css('opacity','0.5');

			var username = jQuery('#woocommerce_tunl_username').val();

			var password = jQuery('#woocommerce_tunl_password').val();
			
			if(jQuery('#woocommerce_tunl_api_mode').is(':checked')){
				var api_mode = 'yes' ; 
			}else{
				var api_mode = 'no' ; 
			}


			jQuery.ajax({

			  type: 'POST',

			  url: adminAjax.ajaxurl,

			  data: { action: 'connect_tunl_payment', username: username, password: password, api_mode: api_mode },

			  success: function(response){

				jQuery('.loader-connect-class').hide();

				jQuery('.btn-connect-payment').css('pointer-events','unset');

				jQuery('.btn-connect-payment').css('opacity','1');

				if( response.status ){

					toastr["success"](response.message);

				}else{

					toastr["error"](response.message);

				}

			  }

			});

		}

	});



	jQuery(document).on('click','.btn-disconnect-payment',function(){

		jQuery('.loader-disconnect-class').show();

		jQuery('.btn-disconnect-payment').css('pointer-events','none');

		jQuery('.btn-disconnect-payment').css('opacity','0.5');

		jQuery.ajax({

			type: 'POST',

			url: adminAjax.ajaxurl,

			data: { action: 'disconnect_tunl_payment' },

			success: function(response){

				jQuery('.loader-disconnect-class').hide();

				jQuery('.btn-disconnect-payment').css('pointer-events','unset');

				jQuery('.btn-disconnect-payment').css('opacity','1');

				if( response.status ){

					toastr["success"](response.message);

					setTimeout(function () {

						location.reload();

					}, 1000);

				}else{

					toastr["error"](response.message);

				}

			}

		});

	});

});