<?php



/**



 * Plugin Name: WooCommerce Tunl Gateway



 * Plugin URI: https://www.brihaspatitech.com/



 * Description: Take credit card payments on your store using Tunl.



 * Author: The Brihaspati Infotech



 * Author URI: https://www.brihaspatitech.com



 * Version: 1.0.0



 */



/** Define the Tunl Pyament Method Url */



define( 'TUNL_TEST_URL', 'https://test-api.tunl.com/api' );



define( 'TUNL_LIVE_URL', 'https://api.tunl.com/api' );



/**  Check whether the woocommerce plugin is active or not */



add_action( 'plugins_loaded', 'woocommerce_gateway_tunl_check_wc' );



function woocommerce_gateway_tunl_check_wc() {



	if ( ! class_exists( 'WooCommerce' ) ) {



		add_action( 'admin_notices', 'woocommerce_tunl_missing_wc_notice' );



		return;



	}



}



if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) return;





/**  Plugin activation hook */



if (!function_exists('tunl_payment_activate')){



    function tunl_payment_activate() {



        require_once( ABSPATH . '/wp-admin/includes/upgrade.php' ); 



    }



    register_activation_hook( __FILE__, 'tunl_payment_activate' );



}



/**  Plugin deactivation hook */



if (!function_exists('tunl_payment_deactivate')){



    function tunl_payment_deactivate() {



    	/**  Reset the tunl payment method form field */



    	$my_options = get_option('woocommerce_tunl_settings');



    	$my_options['enabled'] = 'no';



    	$my_options['title'] = '';



    	$my_options['tunl_token'] = '';



    	$my_options['connect_button'] = 1;



    	$my_options['tunl_merchantId'] = '';



    	$my_options['username'] = '';



    	$my_options['password'] = '';



		update_option('woocommerce_tunl_settings', $my_options);



    }  

    register_deactivation_hook( __FILE__, 'tunl_payment_deactivate' );



}  





/**  Initialize Tunl Class */





add_action( 'plugins_loaded', 'initialize_tunl_class' );



function initialize_tunl_class() {



    class WC_TUNL_Gateway extends WC_Payment_Gateway {



        public function __construct() {



        	/** Set the fields text for payment methods  */



        	$this->id = 'tunl';



        	$this->icon = '';



		    $this->has_fields = true;



		    $this->title = __( 'Tunl', 'tunlwoopay' );



		    $this->method_title = __( 'Tunl', 'tunlwoopay' );



		    $this->method_description = __( 'Tunl works by adding payment fields on the checkout and then sending the details to Tunl for verification.', 'tunlwoopay' );



		    /** load the default credit card form */



		    $this->supports = array( 'default_credit_card_form' );



		    /** load backend options fields */



		    $this->init_form_fields();



		    /** load the settings. */



		    $this->init_settings();



		    $this->enabled = $this->get_option( 'enabled' );



		    $this->api_mode = $this->get_option( 'api_mode', 'no' );



			$this->title = $this->get_option( 'title' );



			$my_options = get_option('woocommerce_tunl_settings');



		    /** Action hook to save the settings */



		    if(is_admin()) {



		        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'save_tunl_payment_details' ) );



		    }



		}



		/**  Save Tunl Payment Methods Fields */



		public function save_tunl_payment_details() {



			$my_options = array();



			if( isset( $_POST['woocommerce_tunl_enabled'] ) ){



				$my_options['enabled'] = 'yes';



			}else{



				$my_options['enabled'] = 'no';



			}



			if( isset( $_POST['woocommerce_tunl_api_mode'] ) ){



				$my_options['api_mode'] = 'yes';



			}else{



				$my_options['api_mode'] = 'no';



			}



			$my_options['title'] = wc_clean( wp_unslash( $_POST['woocommerce_tunl_title'] ) );



			$username   = wc_clean( wp_unslash( $_POST['woocommerce_tunl_username'] ) );



			$password   = wc_clean( wp_unslash( $_POST['woocommerce_tunl_password'] ) );



			$my_options['username'] = $username;



			$my_options['password'] = $password;



			$my_options['connect_button'] = 1;



			$my_options['tunl_token'] = '';



			$my_options['tunl_merchantId'] = '';



			if( !empty($username) && !empty($password) ){



				if( empty( $my_options['api_mode'] ) || ( $my_options['api_mode'] == 'no' ) ){



					$url = TUNL_LIVE_URL.'/auth';



				}else{



					$url = TUNL_TEST_URL.'/auth';



				}

				

				$body = array(



			        'username' => $username,



					'password' => $password,


			  		'scope' => 'PAYMENT_WRITE',
			  		

			  		'lifespan' => 43200,



			    );



				/**  Check authentication with tunl payment api */



				$response = wp_remote_post($url, array(



					'headers'     => array('Content-Type' => 'application/json; charset=utf-8'),



					'body'        => json_encode($body),



					'method'      => 'POST',



					'data_format' => 'body',



				));



			    $result_data = json_decode($response['body'], true);



				if( isset($result_data['code']) ){



					add_action( 'admin_notices', 'tunl_auth_error_action' );



				}else{



					$my_options['connect_button'] = 2;



					$my_options['tunl_token'] = $result_data['token'];



					$my_options['tunl_merchantId'] = $result_data['user']['id'];



				}



			}



			do_action( 'woocommerce_update_option', array( 'id' => 'woocommerce_tunl_settings' ) );



			update_option( 'woocommerce_tunl_settings', $my_options );



		}







		/** load backend options fields */



		public function init_form_fields(){



			$array_fields = array();



			$array_fields['enabled'] = array(



	            'title'       => __( 'Enable/Disable', 'tunlwoopay' ),



	            'label'       => __( 'Enable Tunl', 'tunlwoopay' ),



	            'type'        => 'checkbox',



	            'description' => __( 'This enable the Tunl gateway which allow to accept payment through creadit card.', 'tunlwoopay' ),



	            'default'     => 'no',



	            'desc_tip'    => true



	        );



	        $array_fields['api_mode'] = array(



	            'title'       => __( 'Test Mode', 'tunlwoopay' ),



	            'label'       => __( 'Test Mode', 'tunlwoopay' ),



	            'type'        => 'checkbox',



	            'default'     => 'no',



	            'desc_tip'    => true



	        );



			$array_fields['title'] = array(



	            'title'       => __( 'Title', 'tunlwoopay' ),



	            'type'        => 'text',



				'description' => __( 'This controls the title which the user sees during checkout.', 'tunlwoopay' ),



				'default'     => __( 'Tunl', 'tunlwoopay' ),



				'desc_tip'    => true,



	        );



	        $array_fields['username'] = array(



				'title'       => __( 'API Key', 'tunlwoopay' ),



				'label'       => __( 'API Key', 'tunlwoopay' ),



				'type'        => 'text',



				'desc_tip'    => false



			);



			$array_fields['password'] = array(



				'title'       => __( 'Secret', 'tunlwoopay' ),



				'label'       => __( 'Secret', 'tunlwoopay' ),



				'type'        => 'password',



				'desc_tip'    => false



			);



			if( empty( $this->get_option( 'connect_button' ) ) || ( $this->get_option( 'connect_button' ) == 1 ) ){



			}else{



				$array_fields['tunl_token'] = array(



					'title'       => __( 'Status', 'tunlwoopay' ),



					'label'       => __( 'Status', 'tunlwoopay' ),



					'type'        => 'text',



					'class'       => 'tunl_token_class',



					'desc_tip'    => false



				);



			}







	        $array_fields['connect_button'] = array(



	            'title'       => __( '', 'tunlwoopay' ),



	            'type'        => 'hidden',



	            'default'		  => $this->get_option( 'connect_button' ),



	            'desc_tip'    => false



	        );



		    $this->form_fields = $array_fields;



		}



		/** load the credit card form fields */



		public function payment_fields() {	  



		    ?>



		    <fieldset id="wc-<?php echo esc_attr( $this->id ); ?>-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">



		        <?php do_action( 'woocommerce_credit_card_form_start', $this->id ); ?>

		        <?php echo sprintf(

						'<div class="form-row form-row-wide cardNumberInput">%s</div>',

						wp_kses(

							sprintf(

								__( '<label>Card Number <span class="required">*</span></label>

									<input id="tunl_ccno" type="text" autocomplete="off" class="input-text" name="tunl_cardnumber">' )

							),

							array(

								'label' => true,

								'span' => array(

							        'class'     => array()

							    ),

								'input' => array(

							        'type'      => array(),

							        'name'      => array(),

							        'value'     => array(),

							        'autocomplete'     => array(),

							        'class'     => array(),

							        'maxlength'     => array(),

							        'id'   => array()

							    ),

							)

						)

					); ?>

				<?php echo sprintf(

						'<div class="form-row form-row-first">%s</div>',

						wp_kses(

							sprintf(

								__( '<label>Expiration Date <span class="required">*</span></label>

									<input id="tunl_expdate" type="text" autocomplete="off" placeholder="MM / YY" class="input-text" name="tunl_expirydate">' )

							),

							array(

								'label' => true,

								'span' => array(

							        'class'     => array()

							    ),

								'input' => array(

							        'type'      => array(),

							        'name'      => array(),

							        'value'     => array(),

							        'autocomplete'     => array(),

							        'class'     => array(),

							        'placeholder'     => array(),

							        'id'   => array()

							    ),

							)

						)

					); ?>

				<?php echo sprintf(

						'<div class="form-row form-row-last">%s</div>',

						wp_kses(

							sprintf(

								__( '<label>Security Code <span class="required">*</span></label>

									<input id="tunl_cvc" type="password" autocomplete="off" placeholder="CVV" class="input-text" name="tunl_cardcode">' )

							),

							array(

								'label' => true,

								'span' => array(

							        'class'     => array()

							    ),

								'input' => array(

							        'type'      => array(),

							        'name'      => array(),

							        'value'     => array(),

							        'autocomplete'     => array(),

							        'class'     => array(),

							        'placeholder'     => array(),

							        'id'   => array()

							    ),

							)

						)

					); ?>



		        <div class="clear"></div>



		        <?php do_action( 'woocommerce_credit_card_form_end', $this->id ); ?>



		        <div class="clear"></div>



		    </fieldset>



		    <?php

		

		}



		/**  When place order complete then function work  */



		public function process_payment( $order_id ) {



			global $woocommerce;

		

			// we need it to get any order detailes



			$order = wc_get_order( $order_id );



			$email = $order->get_billing_email();



			$name = $order->get_billing_first_name();



			$lname = $order->get_billing_last_name();



			$phone = $order->get_billing_phone();



			$company = $order->get_billing_company();



			$address_1 = $order->get_billing_address_1();



			$address_2 = $order->get_billing_address_2();



			$city = $order->get_billing_city();



			$state = $order->get_billing_state();



			$country = $order->get_billing_country();



			$postcode = $order->get_billing_postcode();

			

			if( empty($postcode) ){
				$postcode = $order->get_shipping_postcode();
			}



			$get_currency = $order->get_currency();



			$order_id = $order->get_id();



			$get_total = $order->get_total();


			$get_total_tax = $order->get_total_tax();



			$get_shipping_total = $order->get_shipping_total();



			$get_shipping_method = $order->get_shipping_method();



			$get_customer_id = $order->get_customer_id();



			$order_address = $address_1.', '.$country.', '.$state.', '.$city;



			$my_options = get_option('woocommerce_tunl_settings');



			$auth = $my_options['tunl_token'];



			/**  Get the list for contact/customer */



			



			/** Payment process with tunl payment api */



			if( empty( $my_options['api_mode'] ) || ( $my_options['api_mode'] == 'no' ) ){



				$url = TUNL_LIVE_URL.'/payments/merchant/'.$my_options['tunl_merchantId'];



			}else{



				$url = TUNL_TEST_URL.'/payments/merchant/'.$my_options['tunl_merchantId'];



			}

			



			$body = array(



				'account' => $_POST['tunl_cardnumber'],



				'autovault' => 'Y',



				'expdate' => $_POST['tunl_expirydate'],



				'cv' => $_POST['tunl_cardcode'],



				'ordernum' => $order_id,



				'amount' => $get_total,



				'tax' => $get_total_tax,



				'cardholdername' => $name.' '.$lname,



				'street' => $order_address,



				'zip' => $postcode,



				'comments' => $_POST['order_comments'],



				'contactId' => null,



				'custref' => null,



				"accountId" => null,



				'action' => 'sale'



			);



			$response = wp_remote_post($url, array(



				'headers'     => array('Content-Type' => 'application/json; charset=utf-8','Authorization' => 'Bearer '.$auth),



				'body'        => json_encode($body),



				'method'      => 'POST',



				'data_format' => 'body',



			));



			if( !is_wp_error( $response ) ) {



				$result_data = json_decode($response['body'], true);

				

				/** Once Payment is complete and success then save paymentID ( ttid ) */



				if( $result_data['phardcode'] == 'SUCCESS' ){



					$order->payment_complete();



					$order->reduce_order_stock();



					update_post_meta($order_id, 'tunl_paymentid', $result_data['ttid']);



					update_post_meta($order_id, 'check_tunlpayment', 1);



					// some notes to customer (replace true with false to make it private)



					$order->add_order_note( 'Hey, your order is paid! Thank you!', true );



					// Empty cart



					$woocommerce->cart->empty_cart();			



					// Redirect to the thank you page



					return array(



						'result' => 'success',



						'redirect' => $this->get_return_url( $order )



					);



				}else{



					/** If Payment process is failed */



					wc_add_notice(  'Payment failed, Please try again.', 'error' );



					return;



				}



			} else {



				/** If connection error while using payment process flow */



				wc_add_notice(  'Connection error.', 'error' );



				return;



			}		 



		}



    }



}



/**  Script enqueue for plugin on admin */



function custom_scripts_enqueue() {



    //wp_enqueue_script('jquery');



    wp_register_script( 'tunlpaymentJs', plugin_dir_url( __FILE__ ) . 'assets/js/tunl-payment.js','','',true);



    wp_register_script( 'toastrJs', plugin_dir_url( __FILE__ ) . 'assets/js/toastr.min.js','','',true);



    wp_register_script( 'maskJs', plugin_dir_url( __FILE__ ) . 'assets/js/jquery.mask.min.js','','',true);



    wp_register_style( 'tunlpaymentCss', plugin_dir_url( __FILE__ ) . 'assets/css/tunl-payment.css' );



    wp_register_style( 'toastrCss', plugin_dir_url( __FILE__ ) . 'assets/css/toastr.min.css' ); 



    wp_enqueue_script( 'tunlpaymentJs' );



    wp_enqueue_style( 'tunlpaymentCss' );



    wp_enqueue_script( 'toastrJs' );



    wp_enqueue_script( 'maskJs' );



    wp_enqueue_style( 'toastrCss' );



    wp_localize_script( 'tunlpaymentJs', 'adminAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ), 'ajaxloader' => plugin_dir_url( __FILE__ ) . 'assets/images/loader.gif' )); 



}



 

/**  Script enqueue for frontend */



add_action( 'admin_enqueue_scripts', 'custom_scripts_enqueue' );



function custom_frontend_scripts_enqueue() {



    wp_register_script( 'maskJs', plugin_dir_url( __FILE__ ) . 'assets/js/jquery.mask.min.js','','',true);



    wp_register_script( 'tunlfrontpaymentJs', plugin_dir_url( __FILE__ ) . 'assets/js/tunl-front-payment.js','','',true);



    wp_enqueue_script( 'maskJs' ); 



    wp_enqueue_script( 'tunlfrontpaymentJs' );


    
    wp_register_style( 'tunlpaymentFrontCss', plugin_dir_url( __FILE__ ) . 'assets/css/tunl-front-payment.css' );



    wp_enqueue_style( 'tunlpaymentFrontCss' );



    wp_localize_script( 'tunlfrontpaymentJs', 'cardDetail', array( 'cardfolder' => plugin_dir_url( __FILE__ ) . 'assets/images' )); 

}



/** Allow the payment gateway on woocommerce payment setting */



add_action( 'wp_enqueue_scripts', 'custom_frontend_scripts_enqueue' );



add_filter( 'woocommerce_payment_gateways', 'add_custom_gateway_class' );



function add_custom_gateway_class( $gateways ) {



    $gateways[] = 'WC_TUNL_Gateway'; // payment gateway class name



    return $gateways;



}



/** Ajax functionality for connection with tunl payment api */



add_action('wp_ajax_connect_tunl_payment', 'connect_tunl_payment' ); // executed when logged in



add_action('wp_ajax_nopriv_connect_tunl_payment', 'connect_tunl_payment' ); // executed when logged out



function connect_tunl_payment() {



	$api_mode = $_POST['api_mode'];



	$username = $_POST['username'];



	$password = $_POST['password'];



	if( empty( $api_mode ) || ( $api_mode == 'no' ) ){



		$url = TUNL_LIVE_URL.'/auth';



	}else{



		$url = TUNL_TEST_URL.'/auth';



	}

	

	$body = array(



        'username' => $username,



		'password' => $password,


  		'scope' => 'PAYMENT_WRITE',


  		'lifespan' => 43200,



    );



	$response = wp_remote_post($url, array(



		'headers'     => array('Content-Type' => 'application/json; charset=utf-8'),



		'body'        => json_encode($body),



		'method'      => 'POST',



		'data_format' => 'body',



	));



	/** authentication process with tunl payment api */



    $result_data = json_decode($response['body'], true);



	if( isset($result_data['code']) ){



		$resultingData = array(



			'status'  => false,



			'message'  => $result_data['message'],



			'data'	=> array(),



		);



	}else{



		/** once authentication process done then save the token and merchantId save */



		$my_options['username'] = $username;



		$my_options['password'] = $password;



		$my_options['connect_button'] = 2;



		$my_options['tunl_token'] = $result_data['token'];



		$my_options['tunl_merchantId'] = $result_data['user']['id'];



		update_option('woocommerce_tunl_settings', $my_options);



		$resultingData = array(



			'status'  => true,



			'message'  => 'Connected! You can click on Save changes button',



			'data'	=> $result_data,



		);



	}



	wp_send_json($resultingData);  // Return to ajax call 



}



/** Ajax functionality for disconnected with tunl payment api */



add_action('wp_ajax_disconnect_tunl_payment', 'disconnect_tunl_payment' ); // executed when logged in



add_action('wp_ajax_nopriv_disconnect_tunl_payment', 'disconnect_tunl_payment' ); // executed when logged out



function disconnect_tunl_payment() {



	/** Reset token and merchantId */



	$my_options = get_option('woocommerce_tunl_settings');



	$my_options['connect_button'] = 1;



	$my_options['tunl_token'] = '';



	$my_options['tunl_merchantId'] = '';



	update_option('woocommerce_tunl_settings', $my_options);



	$resultingData = array(



		'status'  => true,



		'message'  => 'Disconnected Successfully!',



		'data'	=> array(),



	);



	wp_send_json($resultingData);  // Return to ajax call 



}



// Hook for checkout validation



add_action( 'woocommerce_after_checkout_validation', 'checkout_validation_unique_error', 9999, 2 );



function checkout_validation_unique_error( $data, $errors ){



    // Check for any validation errors



	if( $data['payment_method'] == 'tunl' ){



		$my_options = get_option('woocommerce_tunl_settings');



		if( empty( $my_options['connect_button'] ) || ( $my_options['connect_button'] == 1 ) ){



			// Add a unique custom one



			$errors->add( 'validation', '<strong>Tunl Connection</strong> is pending, So wait for connection.' );



		}else{



			if( empty($_POST['tunl_cardnumber']) ){



				$errors->add( 'validation', '<strong>Card Number</strong> is a required field.' );



			}



			if( empty($_POST['tunl_expirydate']) ){



				$errors->add( 'validation', '<strong>Expiration Date</strong> is a required field.' );



			}



			if( empty($_POST['tunl_cardcode']) ){



				$errors->add( 'validation', '<strong>Security Code</strong> is a required field.' );



			}



			if( !empty($_POST['tunl_cardnumber']) && !empty($_POST['tunl_expirydate']) && !empty($_POST['tunl_cardcode']) ){



				$username = $my_options['username'];



				$password = $my_options['password'];



				if( empty( $my_options['api_mode'] ) || ( $my_options['api_mode'] == 'no' ) ){



					$url = TUNL_LIVE_URL.'/auth';



				}else{



					$url = TUNL_TEST_URL.'/auth';



				}

				

				$body = array(



			        'username' => $username,



					'password' => $password,


			  		'scope' => 'PAYMENT_WRITE',
			  		

			  		'lifespan' => 43200,



			    );



				/** authentication process with tunl payment api */



				$response = wp_remote_post($url, array(



					'headers'     => array('Content-Type' => 'application/json; charset=utf-8'),



					'body'        => json_encode($body),



					'method'      => 'POST',



					'data_format' => 'body',



				));



			    $result_data = json_decode($response['body'], true);



			    if( isset($result_data['code']) ){



					$errors->add( 'validation', '<strong>'.$result_data['message'].'</strong>' );



				}else{

					

					$my_options['tunl_token'] = $result_data['token'];



					$auth = $result_data['token'];



					if( empty( $my_options['api_mode'] ) || ( $my_options['api_mode'] == 'no' ) ){



						$url = TUNL_LIVE_URL.'/payments';



					}else{



						$url = TUNL_TEST_URL.'/payments';



					}



					/** once authentication process done then save the token save */



					update_option('woocommerce_tunl_settings', $my_options);



					$body = array(



						'account' => $_POST['tunl_cardnumber'],



						'expdate' => $_POST['tunl_expirydate'],



						'cv' => $_POST['tunl_cardcode'],



						'action' => 'balanceinq'



					);



					/** Check the validation for credit cards filds using tunl payment api */



					$response = wp_remote_post($url, array(



						'headers'     => array('Content-Type' => 'application/json; charset=utf-8','Authorization' => 'Bearer '.$auth),



						'body'        => json_encode($body),



						'method'      => 'POST',



						'data_format' => 'body',



					));



					$result_data = json_decode($response['body'], true);



					if( isset($result_data['code']) && ( $result_data['code'] == 'PaymentException' || $result_data['code'] == 'AuthenticationException' ) ){



						$errors->add( 'validation', '<strong>'.$result_data['message'].'</strong>' );



					}

				}



			}



		}



	}



}



/** When order staus changed to cancelled by admin */



add_action('woocommerce_order_status_changed', 'tunl_payment_status_change', 10, 3);

function tunl_payment_status_change($order_id, $old_status, $new_status){

	

	if( $new_status == 'cancelled' ){

		

		$tunl_paymentid = get_post_meta($order_id, 'tunl_paymentid');



		$check_tunlpayment = get_post_meta($order_id, 'check_tunlpayment');

		

		if( !empty($check_tunlpayment[0]) ){

			

			$my_options = get_option('woocommerce_tunl_settings');



			if( empty( $my_options['api_mode'] ) || ( $my_options['api_mode'] == 'no' ) ){



				$url = TUNL_LIVE_URL.'/payments/merchant/'.$my_options['tunl_merchantId'].'/'.$tunl_paymentid[0];



			}else{



				$url = TUNL_TEST_URL.'/payments/merchant/'.$my_options['tunl_merchantId'].'/'.$tunl_paymentid[0];



			}



			$auth = $my_options['tunl_token'];



			/** Get the payment details using tunl payment api */



			$response = wp_remote_post($url, array(



				'headers'     => array('Content-Type' => 'application/json; charset=utf-8','Authorization' => 'Bearer '.$auth),



				'method'      => 'GET',



				'data_format' => 'body',



			));



			$resut_data = json_decode($response['body'], true);



			if( empty( $my_options['api_mode'] ) || ( $my_options['api_mode'] == 'no' ) ){



				$url = TUNL_LIVE_URL.'/payments/merchant/'.$my_options['tunl_merchantId'];



			}else{



				$url = TUNL_TEST_URL.'/payments/merchant/'.$my_options['tunl_merchantId'];



			}



			$body = array(



				'accountId' => $resut_data['contactAccount']['id'],



				'contactId' => $resut_data['contact']['id'],



				'amount' => $resut_data['amount'],



				'action' => 'return'



			);



			/** Admin cancelled order then return paymrent to user process with tunl payment api */



			$response = wp_remote_post($url, array(



				'headers'     => array('Content-Type' => 'application/json; charset=utf-8','Authorization' => 'Bearer '.$auth),



				'body'        => json_encode($body),



				'method'      => 'POST',



				'data_format' => 'body',



			));



			$result_data = json_decode($response['body'], true);



			if( $result_data['phardcode'] == 'SUCCESS' ){



				update_post_meta($order_id, 'check_tunlpayment', '');



				$order = new WC_Order($order_id);



				$order->update_status( 'refunded' );



			}



		}

		

	}

	

}



/** Tunl payment admin notice for merchant credentials are not verified */



function tunl_payment_admin_notice() {



	$my_options = get_option('woocommerce_tunl_settings');



	if( empty( ( $my_options['connect_button'] ) || ( $my_options['connect_button'] == 1 ) ) && ( $my_options['api_mode'] == 'yes' ) ){



		echo '<div class="notice notice-warning is-dismissible">

		      	<p>Tunl merchant credentials are not verified. You can authenticate by entering information.</p>

		      </div>'; 



  	}



}

add_action( 'admin_notices', 'tunl_payment_admin_notice' );



/** Tunl payment admin notice for merchant credentials seem to be wrong */



function tunl_auth_error_action() {



	echo '<div class="notice notice-error is-dismissible">

	      	<p>Tunl merchant credentials seem to be wrong. Please enter correct information.</p>

	      </div>';



}



function woocommerce_tunl_missing_wc_notice() {



	echo '<div class="error"><p><strong>' . sprintf( esc_html__( 'Tunl requires WooCommerce to be installed and active. You can download %s here.', 'woocommerce-gateway-stripe' ), '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>' ) . '</strong></p></div>';



}

add_filter('plugin_action_links_'.plugin_basename(__FILE__), 'salcode_add_plugin_page_settings_link');
function salcode_add_plugin_page_settings_link( $links ) {
	$links[] = '<a href="' .
		admin_url( 'admin.php?page=wc-settings&tab=checkout&section=tunl' ) .
		'">' . __('Settings') . '</a>';
	return $links;
}