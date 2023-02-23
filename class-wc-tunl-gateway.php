<?php
/**
Plugin Name: WooCommerce Tunl Gateway
Plugin URI: https://www.brihaspatitech.com/
Description: Take credit card payments on your store using Tunl.
Author: The Brihaspati Infotech
Author URI: https://www.brihaspatitech.com
Version: 1.0.0
 */

/** Define the Tunl Pyament Method Url */



define('TUNL_TEST_URL', 'https://test-api.tunl.com/api');
define('TUNL_LIVE_URL', 'https://api.tunl.com/api');

/**  Check whether the woocommerce plugin is active or not */

add_action('plugins_loaded', 'woocommerce_gateway_tunl_check_wc');

/**
 * Function Name : woocommerce_gateway_tunl_check_wc
 */
function woocommerce_gateway_tunl_check_wc()
{
	if (!class_exists('WooCommerce')) {
		add_action('admin_notices', 'woocommerce_tunl_missing_wc_notice');
		return true;
	}
}

if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
	return;
}

/**  Plugin activation hook */

if (!function_exists('tunl_payment_activate')) {

	/**
	 * Function Name : tunl_payment_activate
	 */
	function tunl_payment_activate()
	{
		require_once(ABSPATH . '/wp-admin/includes/upgrade.php');
	}

	register_activation_hook(__FILE__, 'tunl_payment_activate');

}

/**  Plugin deactivation hook */

if (!function_exists('tunl_payment_deactivate')) {
	/**
	 * Function Name : tunl_payment_deactivate
	 */
	function tunl_payment_deactivate()
	{

		/**  Reset the tunl payment method form field */

		$myOptions = get_option( 'woocommerce_tunl_settings' );
		$myOptions['enabled'] = 'no';
		$myOptions['title'] = '';
		$myOptions['tunl_token'] = '';
		$myOptions['connect_button'] = 1;
		$myOptions['tunl_merchantId'] = '';
		$myOptions['username'] = '';
		$myOptions['password'] = '';
		$myOptions['live_username'] = '';
		$myOptions['live_password'] = '';
		

		update_option( 'woocommerce_tunl_settings', $myOptions );

	}

	register_deactivation_hook(__FILE__, 'tunl_payment_deactivate');

}

/**  Initialize Tunl Class */

add_action('plugins_loaded', 'initialize_tunl_class');

/**
 * Function Name : initialize_tunl_class
 */
function initialize_tunl_class()
{

	/**
	 * Class Name : WCTUNLGateway
	 */
	class WCTUNLGateway extends WC_Payment_Gateway
	{
		/**
		 * Function Name : __construct
		 */
		public function __construct()
		{

			/** Set the fields text for payment methods  */

			$this->id = 'tunl';

			$this->icon = '';

			$this->has_fields = true;

			$this->title = __('Tunl', 'tunlwoopay');

			$this->method_title = __('Tunl', 'tunlwoopay');

			$desp = 'Tunl works by adding payment fields on the checkout and then sending the details to Tunl for verification.';

			$this->method_description = __($desp, 'tunlwoopay');

			/** Load the default credit card form */

			$this->supports = array('default_credit_card_form');

			/** Load backend options fields */

			$this->init_form_fields();

			/** Load the settings. */

			$this->init_settings();

			$this->enabled = $this->get_option('enabled');

			$this->api_mode = $this->get_option('api_mode', 'no');

			$this->title = $this->get_option('title');

			/** Action hook to save the settings */

			if (is_admin()) {
				add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'save_tunlpayment'));
			}

		}

		/**  Save Tunl Payment Methods Fields */
		public function save_tunlpayment() {

			$myOptions = array();

			if (isset($_POST['woocommerce_tunl_enabled'])) {
				$myOptions['enabled'] = 'yes';
			} else {
				$myOptions['enabled'] = 'no';
			}

			if (isset($_POST['woocommerce_tunl_api_mode'])) {
				$myOptions['api_mode'] = 'yes';
			} else {
				$myOptions['api_mode'] = 'no';
			}

			if (isset($_POST['woocommerce_tunl_title'])) {
				$myOptions['title'] = sanitize_text_field(wp_unslash($_POST['woocommerce_tunl_title']));
			} else {
				$myOptions['title'] = '';
			}

			if (isset($_POST['woocommerce_tunl_username'])) {
				$username = sanitize_text_field(wp_unslash($_POST['woocommerce_tunl_username']));
			} else {
				$username = '';
			}

			if (isset($_POST['woocommerce_tunl_password'])) {
				$password = sanitize_text_field(wp_unslash($_POST['woocommerce_tunl_password']));
			} else {
				$password = '';
			}

			if (isset($_POST['woocommerce_tunl_live_username'])) {
				$liveusername = sanitize_text_field(wp_unslash($_POST['woocommerce_tunl_live_username']));
			} else {
				$liveusername = '';
			}

			if (isset($_POST['woocommerce_tunl_live_password'])) {
				$livepassword = sanitize_text_field(wp_unslash($_POST['woocommerce_tunl_live_password']));
			} else {
				$livepassword = '';
			}

			$myOptionsData = get_option('woocommerce_tunl_settings');
			if ($myOptionsData['api_mode'] != $myOptions['api_mode']) {
				$setpaymentpost = 1;
				if (empty($myOptions['api_mode']) || ($myOptions['api_mode'] == 'no')) {
					if (substr($myOptionsData['live_password'], -4) === str_replace('*', '', $livepassword)) {
						$livepassword = apply_filters('tunl_decrypt_filter', $myOptionsData['live_password']);
					}
				} else {
					if (substr($myOptionsData['password'], -4) === str_replace('*', '', $password)) {
						$password = apply_filters('tunl_decrypt_filter', $myOptionsData['password']);
					}
				}
			} else {
				if (empty( $myOptions['api_mode']) || ($myOptions['api_mode'] == 'no')) {
					if ($liveusername === $myOptionsData['live_username']) {
						if (substr($myOptionsData['live_password'], -4) === str_replace('*', '', $livepassword)) {
							$setpaymentpost = 0;
						} else {
							$setpaymentpost = 1;
						}
					} else {
						$setpaymentpost = 1;
					}
				} else {
					if ($username === $myOptionsData['username']) {
						if (substr($myOptionsData['password'], -4) === str_replace('*', '', $password)) {
							$setpaymentpost = 0;
						} else {
							$setpaymentpost = 1;
						}
					} else {
						$setpaymentpost = 1;
					}
				}
			}
			
			if ($setpaymentpost == 1) {
				$myOptions['connect_button']  = 1;
				$myOptions['tunl_token']      = '';
				$myOptions['tunl_merchantId'] = '';
				$myOptions['username']        = $username;
				$myOptions['password']        = $password;
				$myOptions['live_username']        = $liveusername;
				$myOptions['live_password']        = $livepassword;
				
				if (!empty($username) && !empty($password)) {
					if (empty($myOptions['api_mode']) || ($myOptions['api_mode'] == 'no')) {
						$url = TUNL_LIVE_URL . '/auth';
						$checkusername = $liveusername;
						$checkpassword = $livepassword;
					} else {
						$url = TUNL_TEST_URL . '/auth';
						$checkusername = $username;
						$checkpassword = $password;
					}

					$body = array(
						'username' => $checkusername,
						'password' => $checkpassword,
						'scope'    => 'PAYMENT_WRITE',
						'lifespan' => 43200,
					);

					/**  Check authentication with tunl payment api */
					$response = wp_remote_post(
						$url,
						array(
							'headers'     => array('Content-Type' => 'application/json; charset=utf-8'),
							'body'        => wp_json_encode($body),
							'method'      => 'POST',
							'data_format' => 'body',
						)
					);

					$resultdata = json_decode( $response['body'], true );

					if ( isset( $resultdata['code'] ) ) {
						add_action( 'admin_notices', 'tunl_auth_error_action' );
						if ( empty( $myOptions['api_mode'] ) || ( $myOptions['api_mode'] == 'no' ) ) {
							$myOptions['live_password'] = sanitize_text_field( wp_unslash( $_POST['woocommerce_tunl_live_password'] ) );
							$myOptions['password'] = $myOptionsData['password'];
						} else {
							$myOptions['password'] = sanitize_text_field( wp_unslash( $_POST['woocommerce_tunl_password'] ) );
							$myOptions['live_password'] = $myOptionsData['live_password'];
						}
					} else {
						$myOptions['connect_button']  = 2;
						if ( empty( $myOptions['api_mode'] ) || ( $myOptions['api_mode'] == 'no' ) ) {
							$myOptions['live_password']      = apply_filters( 'tunl_encrypt_filter', $livepassword );
							$myOptions['password'] = $myOptionsData['password'];
						} else {
							$myOptions['password']      = apply_filters( 'tunl_encrypt_filter', $password );
							$myOptions['live_password'] = $myOptionsData['live_password'];
						}
						$myOptions['tunl_token']      = $resultdata['token'];
						$myOptions['tunl_merchantId'] = $resultdata['user']['id'];
					}
				}
			} else {
				$myOptions['username']        = $myOptionsData['username'];
				$myOptions['password']        = $myOptionsData['password'];
				$myOptions['live_username']        = $myOptionsData['live_username'];
				$myOptions['live_password']        = $myOptionsData['live_password'];
				$myOptions['connect_button']  = $myOptionsData['connect_button'];
				$myOptions['tunl_token']      = $myOptionsData['tunl_token'];
				$myOptions['tunl_merchantId'] = $myOptionsData['tunl_merchantId'];
			}
			
			do_action( 'woocommerce_update_option', array( 'id' => 'woocommerce_tunl_settings' ) );
			update_option( 'woocommerce_tunl_settings', $myOptions );
		}

		/** Load backend options fields */
		public function init_form_fields() {
			$arrayfields = array();
			$arrayfields['enabled'] = array(
				'title'       => __( 'Enable/Disable', 'tunlwoopay' ),
				'label'       => __( 'Enable Tunl', 'tunlwoopay' ),
				'type'        => 'checkbox',
				'description' => __( 'This enable Tunl gateway which allow to accept payment through creadit card.', 'tunlwoopay' ),
				'default'     => 'no',
				'desc_tip'    => true,
			);
			$arrayfields['api_mode'] = array(
				'title'    => __( 'Test Mode', 'tunlwoopay' ),
				'label'    => __( 'Test Mode', 'tunlwoopay' ),
				'type'     => 'checkbox',
				'default'  => 'no',
				'desc_tip' => true,
			);
			$arrayfields['title'] = array(
				'title'       => __( 'Title', 'tunlwoopay' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'tunlwoopay' ),
				'default'     => __( 'Tunl', 'tunlwoopay' ),
				'desc_tip'    => true,
			);
			$arrayfields['username'] = array(
				'title'    => __( 'API Key', 'tunlwoopay' ),
				'label'    => __( 'API Key', 'tunlwoopay' ),
				'type'     => 'text',
				'desc_tip' => false,
			);
			$arrayfields['password'] = array(
				'title'    => __( 'Secret', 'tunlwoopay' ),
				'label'    => __( 'Secret', 'tunlwoopay' ),
				'type'     => 'text',
				'desc_tip' => false,
			);
			$arrayfields['live_username'] = array(
				'title'    => __( 'API Key', 'tunlwoopay' ),
				'label'    => __( 'API Key', 'tunlwoopay' ),
				'type'     => 'text',
				'desc_tip' => false,
			);
			$arrayfields['live_password'] = array(
				'title'    => __( 'Secret', 'tunlwoopay' ),
				'label'    => __( 'Secret', 'tunlwoopay' ),
				'type'     => 'text',
				'desc_tip' => false,
			);
			if ( empty( $this->get_option( 'connect_button' ) ) || ( $this->get_option( 'connect_button' ) == 1 ) ) {
				
			} else {
				$arrayfields['tunl_token'] = array(
					'title'    => __( 'Status', 'tunlwoopay' ),
					'label'    => __( 'Status', 'tunlwoopay' ),
					'type'     => 'text',
					'class'    => 'tunl_token_class',
					'desc_tip' => false,
				);
			}
			$arrayfields['connect_button'] = array(
				'title'    => __( 'Connect', 'tunlwoopay' ),
				'type'     => 'hidden',
				'default'  => $this->get_option( 'connect_button' ),
				'desc_tip' => false,
			);
			$this->form_fields = $arrayfields;
		}

		/** Load the credit card form fields */
		public function payment_fields() { ?>
			<fieldset id="wc-<?php echo esc_attr( $this->id ); ?>-cc-form" class="wc-credit-card-form wc-payment-form tunlcform">
				<?php do_action( 'woocommerce_credit_card_form_start', $this->id ); ?>
				<?php
					echo sprintf(
						'<div class="form-row form-row-wide cardNumberInput">%s</div>',
						wp_kses(
							sprintf(
								__( '<label>Card Number <span class="required">*</span></label>
									<div class="card_number_input">
									<input id="tunl_ccno" type="text" autocomplete="off" class="input-text" name="tunl_cardnumber">
									</div>' )
							),
							array(
								'label' => true,
								'div'   => array(
									'class' => array(),
								),
								'span'  => array(
									'class' => array(),
								),
								'input' => array(
									'type'         => array(),
									'name'         => array(),
									'value'        => array(),
									'autocomplete' => array(),
									'class'        => array(),
									'maxlength'    => array(),
									'id'           => array(),
								),
							)
						)
					);
				?>
				<?php
					echo sprintf(
						'<div class="form-row form-row-first">%s</div>',
						wp_kses(
							sprintf(
								__( '<label>Expiration Date <span class="required">*</span></label>
									<input id="tunl_expdate" type="text" placeholder="MM / YY" class="input-text" name="tunl_expirydate">' )
							),
							array(
								'label' => true,
								'span'  => array(
									'class' => array(),
								),
								'input' => array(
									'type'         => array(),
									'name'         => array(),
									'value'        => array(),
									'autocomplete' => array(),
									'class'        => array(),
									'placeholder'  => array(),
									'id'           => array(),
								),
							)
						)
					);
				?>
				<?php
					echo sprintf(
						'<div class="form-row form-row-last">%s</div>',
						wp_kses(
							sprintf(
								__( '<label>Security Code <span class="required">*</span></label>
								<input id="tunl_cvc" type="password" placeholder="CVV" class="input-text" name="tunl_cardcode">' )
							),
							array(
								'label' => true,
								'span'  => array(
									'class' => array(),
								),
								'input' => array(
									'type'         => array(),
									'name'         => array(),
									'value'        => array(),
									'autocomplete' => array(),
									'class'        => array(),
									'placeholder'  => array(),
									'id'           => array(),
								),
							)
						)
					);
				?>
				<div class="clear"></div>
				<?php do_action( 'woocommerce_credit_card_form_end', $this->id ); ?>
				<div class="clear"></div>
			</fieldset>
			<?php
		}

		/**
		 * When place order complete then function work
		 *
		 * @param int $orderid orderid.
		 */
		public function process_payment( $orderid ) {
			global $woocommerce;

			/** We need it to get any order detailes */
			$order = wc_get_order( $orderid );
			$name = $order->get_billing_first_name();
			$lname = $order->get_billing_last_name();
			$addressorder = $order->get_billing_address_1();
			$city = $order->get_billing_city();
			$state = $order->get_billing_state();
			$country = $order->get_billing_country();
			$postcode = $order->get_billing_postcode();
			if ( empty( $postcode ) ) {
				$postcode = $order->get_shipping_postcode();
			}
			$orderid = $order->get_id();
			$gettotal = $order->get_total();
			$gettotaltax = $order->get_total_tax();
			$orderaddress = $addressorder . ', ' . $country . ', ' . $state . ', ' . $city;
			$myOptions = get_option( 'woocommerce_tunl_settings' );
			$auth = $myOptions['tunl_token'];

			/** Payment process with tunl payment api */

			if ( empty( $myOptions['api_mode'] ) || ( $myOptions['api_mode'] == 'no' ) ) {
				$url = TUNL_LIVE_URL . '/payments/merchant/' . $myOptions['tunl_merchantId'];
			} else {
				$url = TUNL_TEST_URL . '/payments/merchant/' . $myOptions['tunl_merchantId'];
			}
			$body = array(
				'account' => $_POST['tunl_cardnumber'],
				'autovault'      => 'Y',
				'expdate'        => $_POST['tunl_expirydate'],
				'cv'             => $_POST['tunl_cardcode'],
				'ordernum'       => $orderid,
				'amount'         => $gettotal,
				'tax'            => $gettotaltax,
				'cardholdername' => $name . ' ' . $lname,
				'street'         => $orderaddress,
				'zip'            => $postcode,
				'comments'       => $_POST['order_comments'],
				'contactId'      => null,
				'custref'        => null,
				'accountId'      => null,
				'action'         => 'sale',
			);
			$response = wp_remote_post(
				$url,
				array(
					'headers'     => array(
									'Content-Type'  => 'application/json; charset=utf-8',
									'Authorization' => 'Bearer ' . $auth,
								),
					'body'        => wp_json_encode( $body ),
					'method'      => 'POST',
					'data_format' => 'body',
				)
			);
			if ( ! is_wp_error( $response ) ) {
				$resultData = json_decode( $response['body'], true );

				/** Once Payment is complete and success then save paymentID ( ttid ) */
				if ( $resultData['phardcode'] == 'SUCCESS' ) {
					$order->payment_complete();
					$order->reduce_order_stock();
					update_post_meta( $orderid, 'tunl_paymentid', $resultData['ttid'] );
					update_post_meta( $orderid, 'check_tunlpayment', 1 );

					/** Some notes to customer (replace true with false to make it private) */
					$order->add_order_note( 'Hey, your order is paid! Thank you!', true );

					/** Empty cart */
					$woocommerce->cart->empty_cart();

					/** Redirect to the thank you page */
					return array(
						'result'   => 'success',
						'redirect' => $this->get_return_url( $order ),
					);
				} else {
					/** If Payment process is failed */
					wc_add_notice( 'Payment failed, Please try again.', 'error' );
					return true;
				}
			} else {

				/** If connection error while using payment process flow */
				wc_add_notice( 'Connection error.', 'error' );
				return true;
			}
		}
	}
}

/** Script enqueue for plugin on admin */
function custom_scripts_enqueue() {
	wp_enqueue_script('tunlpaymentJs',plugin_dir_url( __FILE__ ).'assets/js/tunl-payment.js',array('jquery'),'1.0',true);
	wp_enqueue_script('toastrJs',plugin_dir_url( __FILE__ ) . 'assets/js/toastr.min.js',array('jquery'),'1.0',true);
	wp_enqueue_script('maskJs',plugin_dir_url( __FILE__ ) . 'assets/js/jquery.mask.min.js',array('jquery'),'1.0',true);
	wp_enqueue_style('tunlpaymentCss',plugin_dir_url( __FILE__ ) . 'assets/css/tunl-payment.css',array(),'1.0','all');
	wp_enqueue_style( 'toastrCss', plugin_dir_url( __FILE__ ) . 'assets/css/toastr.min.css', array(), '1.0', 'all' );
	wp_localize_script(
		'tunlpaymentJs',
		'adminAjax',
		array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'ajaxloader' => plugin_dir_url( __FILE__ ) . 'assets/images/loader.gif'
			)
		);
}
add_action( 'admin_enqueue_scripts', 'custom_scripts_enqueue' );

/**  Script enqueue for frontend */
function custom_frontend_scripts_enqueue() {
	wp_enqueue_script('maskJs',plugin_dir_url( __FILE__ ) . 'assets/js/jquery.mask.min.js',array('jquery'),'1.0',true);
	wp_enqueue_script('frontTunl',plugin_dir_url( __FILE__ ).'assets/js/tunl-front-payment.js',array('jquery'),'1.0',true);
	wp_enqueue_style('tunlFrontCss',plugin_dir_url( __FILE__ ).'assets/css/tunl-front-payment.css',array(),'1.0','all');
	wp_localize_script( 'frontTunl', 'cardDetail', array( 'cardfolder' => plugin_dir_url( __FILE__ ) . 'assets/images' ) );
}
add_action( 'wp_enqueue_scripts', 'custom_frontend_scripts_enqueue' );

/**
 * Allow the payment gateway on woocommerce payment setting
 *
 * @param int $gateways gateways.
 */
function add_custom_gateway_class( $gateways ) {
	$gateways[] = 'WCTUNLGateway';
	return $gateways;
}
add_filter( 'woocommerce_payment_gateways', 'add_custom_gateway_class' );

/** Ajax functionality for connection with tunl payment api */
function connect_tunl_payment()
{

	$apiMode = $_POST['api_mode'];
	$username = $_POST['username'];
	$password = $_POST['password'];
	$myOptionsData = get_option('woocommerce_tunl_settings');
	if (empty($apiMode) || ($apiMode == 'no')) {
		if (substr($myOptionsData['live_password'], -4) === str_replace('*', '', $password)) {
			$password = apply_filters('tunl_decrypt_filter', $myOptionsData['live_password']);
		}
	} else {
		if (substr($myOptionsData['password'], -4) === str_replace('*', '', $password)) {
			$password = apply_filters('tunl_decrypt_filter', $myOptionsData['password']);
		}
	}
	if (empty($apiMode) || ($apiMode == 'no')) {
		$url = TUNL_LIVE_URL . '/auth';
	} else {
		$url = TUNL_TEST_URL . '/auth';
	}
	$body = array(
		'username' => $username,
		'password' => $password,
		'scope'    => 'PAYMENT_WRITE',
		'lifespan' => 43200,
	);
	$response = wp_remote_post(
		$url,
		array(
			'headers'     => array( 'Content-Type' => 'application/json; charset=utf-8' ),
			'body'        => wp_json_encode( $body ),
			'method'      => 'POST',
			'data_format' => 'body',
		)
	);

	/** Authentication process with tunl payment api */
	$resultData = json_decode( $response['body'], true );
	if ( isset( $resultData['code'] ) ) {
		$resultingData = array(
			'status' => false,
			'message' => $resultData['message'],
			'data' => array(),
		);
	}else{

		/** once authentication process done then save the token and merchantId save */
		if ( isset( $_POST['tunl_enabled'] ) ) {
			$myOptions['enabled'] = 'yes';
		} else {
			$myOptions['enabled'] = 'no';
		}

		if ( isset( $_POST['api_mode'] ) ) {
			$myOptions['api_mode'] = 'yes';
			$myOptions['username'] = $username;
			$myOptions['password'] = apply_filters( 'tunl_encrypt_filter', $password );
			$myOptions['live_username'] = $myOptionsData['live_username'];
			$myOptions['live_password'] = $myOptionsData['live_password'];
		} else {
			$myOptions['api_mode'] = 'no';
			$myOptions['live_username'] = $username;
			$myOptions['live_password'] = apply_filters( 'tunl_encrypt_filter', $password );
			$myOptions['username'] = $myOptionsData['username'];
			$myOptions['password'] = $myOptionsData['password'];
		}

		$myOptions['title'] = $_POST['tunl_title'];
		$myOptions['connect_button'] = 2;
		$myOptions['tunl_token'] = $resultData['token'];
		$myOptions['tunl_merchantId'] = $resultData['user']['id'];
		update_option( 'woocommerce_tunl_settings', $myOptions );
		$resultingData = array(
			'status' => true,
			'message' => 'Connected! You can click on Save changes button',
			'data' => $resultData,
		);
	}
	wp_send_json( $resultingData );
}
add_action('wp_ajax_connect_tunl_payment', 'connect_tunl_payment' );
add_action('wp_ajax_nopriv_connect_tunl_payment', 'connect_tunl_payment' );

/** Hook for checkout validation */

add_action( 'woocommerce_after_checkout_validation', 'tunl_checkout_validation_unique_error', 9999, 2 );
function tunl_checkout_validation_unique_error( $data, $errors ){

    /** Check for any validation errors */
	if( $data['payment_method'] == 'tunl' ){
		$myOptions = get_option('woocommerce_tunl_settings');
		if( empty( $myOptions['connect_button'] ) || ( $myOptions['connect_button'] == 1 ) ){

			/** Add a unique custom one */
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
				$username = $myOptions['username'];
				$password = apply_filters('tunl_decrypt_filter', $myOptions['password']);
				$liveusername = $myOptions['live_username'];
				$livepassword = apply_filters('tunl_decrypt_filter', $myOptions['live_password']);

				if( empty( $myOptions['api_mode'] ) || ( $myOptions['api_mode'] == 'no' ) ){
					$url = TUNL_LIVE_URL.'/auth';
					$checkUsername = $liveusername;
					$checkPassword = $livepassword;
				}else{
					$url = TUNL_TEST_URL.'/auth';
					$checkUsername = $username;
					$checkPassword = $password;
				}

				$body = array(
			        'username' => $checkUsername,
					'password' => $checkPassword,
			  		'scope' => 'PAYMENT_WRITE',
			  		'lifespan' => 43200,
			    );

				/** authentication process with tunl payment api */
				$response = wp_remote_post($url, array(
					'headers'     => array('Content-Type' => 'application/json; charset=utf-8'),
					'body'        => wp_json_encode($body),
					'method'      => 'POST',
					'data_format' => 'body',
				));
			    $resultData = json_decode($response['body'], true);

			    if ( isset($resultData['code']) ) {
					$errors->add( 'validation', '<strong>'.$resultData['message'].'</strong>' );
				} else {
					$myOptions['tunl_token'] = $resultData['token'];
					$auth = $resultData['token'];

					if( empty( $myOptions['api_mode'] ) || ( $myOptions['api_mode'] == 'no' ) ){
						$url = TUNL_LIVE_URL.'/payments';
					}else{
						$url = TUNL_TEST_URL.'/payments';
					}

					/** once authentication process done then save the token save */
					update_option('woocommerce_tunl_settings', $myOptions);
					$body = array(
						'account' => $_POST['tunl_cardnumber'],
						'expdate' => $_POST['tunl_expirydate'],
						'cv' => $_POST['tunl_cardcode'],
						'action' => 'balanceinq',
					);

					/** Check the validation for credit cards filds using tunl payment api */
					$response = wp_remote_post($url, array(
						'headers'     => array('Content-Type' => 'application/json; charset=utf-8','Authorization' => 'Bearer '.$auth),
						'body'        => wp_json_encode($body),
						'method'      => 'POST',
						'data_format' => 'body',
					));
					$rsD = json_decode($response['body'], true);

					if( isset($rsD['code']) && ( $rsD['code'] == 'PaymentException' || $rsD['code'] == 'AuthenticationException' ) ){
						$errors->add( 'validation', '<strong>'.$rsD['message'].'</strong>' );
					}

				}
			}
		}
	}
}

/** Tunl payment admin notice for merchant credentials are not verified */
function tunl_payment_admin_notice() {
	$myOpts = get_option('woocommerce_tunl_settings');

	if( empty( ( $myOpts['connect_button'] ) || ( $myOpts['connect_button'] == 1 ) ) && ( $myOpts['api_mode'] == 'yes' ) ){

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
	echo '<div class="error">
			<p><strong>' . sprintf(
				esc_html__(
					'Tunl requires WooCommerce to be installed and active. You can download %s here.',
					'woocommerce-gateway-stripe'
				),
				'<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>' ) . '</strong></p></div>';
}

add_filter('plugin_action_links_'.plugin_basename(__FILE__), 'tunl_add_plugin_page_settings_link');
function tunl_add_plugin_page_settings_link( $links ) {
	$returnArray = [];
	$returnArray[] = '<a href="' .
		admin_url( 'admin.php?page=wc-settings&tab=checkout&section=tunl' ) .
		'">' . __('Settings') . '</a>';
	$returnArray[] = $links['deactivate'];
	return $returnArray;
}

add_action( 'woocommerce_order_refunded', 'action_woocommerce_order_refunded', 10, 2 );
function action_woocommerce_order_refunded( $order_id, $refund_id )
{
	if ($_POST['line_item_tax_totals']) {
		$taxTotals = json_decode( sanitize_text_field( wp_unslash( $_POST['line_item_tax_totals'] ) ), true );
	}else{
		$taxTotals = array();
	}
	
	if( !empty($_POST['refund_amount']) ){

		$order = new WC_Order($order_id);

		$total_amount_refund = $_POST['refund_amount'];

		$total_tax_refund = 0;

		foreach( $taxTotals as $single_item ){

			$total_tax_refund = $total_tax_refund + array_sum($single_item);

		}

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

				'amount' => $total_amount_refund,

				'tax' => $total_tax_refund,

				'action' => 'return'

			);

			/** Admin cancelled order then return payment to user process with tunl payment api */

			$response = wp_remote_post($url, array(

				'headers'     => array('Content-Type' => 'application/json; charset=utf-8','Authorization' => 'Bearer '.$auth),

				'body'        => wp_json_encode($body),

				'method'      => 'POST',

				'data_format' => 'body',

			));

			$result_data = json_decode($response['body'], true);

			if( $result_data['phardcode'] == 'SUCCESS' ){

				$order = new WC_Order($order_id);

				if( empty($_POST['refund_reason']) ){

					$set_reason = 'N/A';

				}else{

					$set_reason = $_POST['refund_reason'];

				}

				$note = "Refunded $".$total_amount_refund." - Refund ID: ".$refund_id." - Reason: ".$set_reason;

				$order->add_order_note( $note );

				$order->save();

			}

		}
	}
}


/**  Text should be encrypted as (AES-256) */
add_filter( 'tunl_encrypt_filter', 'tunl_encrypt_key_function', 10, 1 );

function tunl_encrypt_key_function( $plaintext ){

	$iv = '';

	$ciphertext = openssl_encrypt($plaintext, 'aes-256-cbc', '956560z4abzr4eb0bew5e512b39uq4s1', OPENSSL_RAW_DATA, $iv);

	$ivCiphertext = $iv . $ciphertext;

	$ivCiphertextB64 = base64_encode($ivCiphertext);

	return $ivCiphertextB64;

}

/**  Encrypted text should be decrypt as plain text */
add_filter( 'tunl_decrypt_filter', 'tunl_decrypt_key_function', 10, 1 );

function tunl_decrypt_key_function( $ivCiphertextB64 ){

	$ivCiphertext  = base64_decode($ivCiphertextB64);

	$iv = '';

	$ciphetext = $ivCiphertext;

	$decryptedData = openssl_decrypt($ciphetext, "aes-256-cbc", '956560z4abzr4eb0bew5e512b39uq4s1', OPENSSL_RAW_DATA, $iv);

	return $decryptedData;

}