jQuery(document).ready(function(){

	jQuery('#woocommerce_tunl_password').parent().parent().parent().addClass('sandbox_tunl_class');
	jQuery('#woocommerce_tunl_username').parent().parent().parent().addClass('sandbox_tunl_class');

	jQuery('#woocommerce_tunl_live_password').parent().parent().parent().addClass('live_tunl_class');
	jQuery('#woocommerce_tunl_live_username').parent().parent().parent().addClass('live_tunl_class');

	
	if( jQuery('#woocommerce_tunl_api_mode').prop('checked') == true ){
		jQuery('.sandbox_tunl_class').show();
	}else{
		jQuery('.live_tunl_class').show();
	}

	jQuery(document).on('change','#woocommerce_tunl_api_mode',function() {
	    if( jQuery(this).prop('checked') == true ){
			jQuery('.sandbox_tunl_class').show();
			jQuery('.live_tunl_class').hide();
		}else{
			jQuery('.live_tunl_class').show();
			jQuery('.sandbox_tunl_class').hide();
		}
	})

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

	str_secret(jQuery('#woocommerce_tunl_password').val());
	str_secret_live(jQuery('#woocommerce_tunl_live_password').val());
	jQuery(document).on('keyup','#woocommerce_tunl_password',function(){
		// str_secret(jQuery(this).val());
	});

	function str_secret(str_text){
		var trailingCharsIntactCount = 4;
		var str = str_text;
		if( str.length > 4 ){
			str = new Array(28).join('*') + str.slice(-trailingCharsIntactCount);
		}
		jQuery('#woocommerce_tunl_password').val(str);
		jQuery('#woocommerce_tunl_password').attr('value',str);
	}

	function str_secret_live(str_text){
		var trailingCharsIntactCount = 4;
		var str = str_text;
		if( str.length > 4 ){
			str = new Array(28).join('*') + str.slice(-trailingCharsIntactCount);
		}
		jQuery('#woocommerce_tunl_live_password').val(str);
		jQuery('#woocommerce_tunl_live_password').attr('value',str);
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
			var tunl_title = jQuery('#woocommerce_tunl_title').val();

			if(jQuery('#woocommerce_tunl_api_mode').is(':checked')){
				var api_mode = 'yes' ;
				var username = jQuery('#woocommerce_tunl_username').val();
				var password = jQuery('#woocommerce_tunl_password').val();
			}else{
				var api_mode = 'no' ; 
				var username = jQuery('#woocommerce_tunl_live_username').val();
				var password = jQuery('#woocommerce_tunl_live_password').val();
			}

			if(jQuery('#woocommerce_tunl_enabled').is(':checked')){
				var tunl_enabled = 'yes' ; 
			}else{
				var tunl_enabled = 'no' ; 
			}

			window.onbeforeunload = null;
			jQuery.ajax({
			  type: 'POST',
			  url: adminAjax.ajaxurl,
			  data: { action: 'connect_tunl_payment', tunl_title: tunl_title, username: username, password: password, api_mode: api_mode, tunl_enabled: tunl_enabled },
			  success: function(response){
				jQuery('.loader-connect-class').hide();
				jQuery('.btn-connect-payment').css('pointer-events','unset');
				jQuery('.btn-connect-payment').css('opacity','1');

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