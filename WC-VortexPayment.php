<?php
/*
 * Plugin Name: WooCommerce VortexPayment Gateway
 * Description: Take credit card payments on your store.
 * Author: VortexPayment 
 * Author URI: http://vortex-solutions.com.mx/
 * Version: 1.0.0
 */
/*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter( 'woocommerce_payment_gateways', 'VortexPayment_add_gateway_class' );
function VortexPayment_add_gateway_class( $gateways ) {
	$gateways[] = 'WC_VortexPayment_Gateway'; // your class name is here
	return $gateways;
}
 
/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */
add_action( 'plugins_loaded', 'VortexPayment_init_gateway_class' );
function VortexPayment_init_gateway_class() {
 
	class WC_VortexPayment_Gateway extends WC_Payment_Gateway {
 
 		/**
 		 * Class constructor, more about it in Step 3
 		 */
          public function __construct() {
 
            $this->id = 'misha'; // payment gateway plugin ID
            $this->icon = 'http://vortex-solutions.com.mx/media/icons/VortexPaymentLogo.png'; // URL of the icon that will be displayed on checkout page near your gateway name
            $this->has_fields = true; // in case you need a custom credit card form
            $this->method_title = 'Vortex Payment Gateway';
            $this->method_description = 'Acepta todas las tarjetas con Vortex Payment'; // will be displayed on the options page
         
            // gateways can support subscriptions, refunds, saved payment methods,
            // but in this tutorial we begin with simple payments
            $this->supports = array(
                'products'
            );
         
            // Method with all the options fields
            $this->init_form_fields();
         
            // Load the settings.
            $this->init_settings();
            $this->title = $this->get_option( 'title' );
            $this->description = $this->get_option( 'description' );
            $this->enabled = $this->get_option( 'enabled' );
            $this->testmode = 'yes' === $this->get_option( 'testmode' );
            $this->private_key = $this->testmode ? $this->get_option( 'test_private_key' ) : $this->get_option( 'private_key' );
            $this->publishable_key = $this->testmode ? $this->get_option( 'test_publishable_key' ) : $this->get_option( 'publishable_key' );
         
            // This action hook saves the settings
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
         
            // We need custom JavaScript to obtain a token
            add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
         
            // You can also register a webhook here
            // add_action( 'woocommerce_api_{webhook name}', array( $this, 'webhook' ) );
         }
 
		/**
 		 * Plugin options, we deal with it in Step 3 too
 		 */
          public function init_form_fields(){
 
            $this->form_fields = array(
                'enabled' => array(
                    'title'       => 'Activado/Desactivado',
                    'label'       => 'Activar Vortex Payment Gateway',
                    'type'        => 'checkbox',
                    'description' => '',
                    'default'     => 'yes'
                ),
                'title' => array(
                    'title'       => 'Título',
                    'type'        => 'text',
                    'description' => 'Esto controla el título que el usuario ve durante el pago.',
                    'default'     => 'Vortex Payment',
                    'desc_tip'    => true,
                ),
                'description' => array(
                    'title'       => 'Descripción',
                    'type'        => 'textarea',
                    'description' => 'Esto controla la descripción que el usuario ve durante el proceso de pago.',
                    'default'     => 'Paga con tu tarjeta de Débito/Crédito.',
                ),
                'testmode' => array(
                    'title'       => 'Modo de prueba',
                    'label'       => 'Activa el modo de prueba',
                    'type'        => 'checkbox',
                    'description' => 'Coloque la pasarela de pago en modo de prueba utilizando claves de API de prueba.',
                    'default'     => 'yes',
                    'desc_tip'    => true,
                ),
                'test_publishable_key' => array(
                    'title'       => 'Test Publishable Key',
                    'type'        => 'text'
                ),
                'test_private_key' => array(
                    'title'       => 'Test Private Key',
                    'type'        => 'password',
                ),
                'publishable_key' => array(
                    'title'       => 'Live Publishable Key',
                    'type'        => 'text'
                ),
                'private_key' => array(
                    'title'       => 'Live Private Key',
                    'type'        => 'password'
                )
            );
        }
 
		/**
		 * You will need it if you want your custom credit card form, Step 4 is about it
		 */
		public function payment_fields() {
 
            // ok, let's display some description before the payment form
            if ( $this->description ) {
                // you can instructions for test mode, I mean test card numbers etc.
                if ( $this->testmode ) {
                    $this->description .= ' TEST MODE ENABLED. In test mode, you can use the card numbers listed in <a href="#" target="_blank" rel="noopener noreferrer">documentation</a>.';
                    $this->description  = trim( $this->description );
                }
                // display the description with <p> tags etc.
                echo wpautop( wp_kses_post( $this->description ) );
            }
         
            // I will echo() the form, but you can close PHP tags and print it directly in HTML
            echo '<fieldset id="wc-' . esc_attr( $this->id ) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">';
         
            // Add this action hook if you want your custom payment gateway to support it
            do_action( 'woocommerce_credit_card_form_start', $this->id );
         
            // I recommend to use inique IDs, because other gateways could already use #ccNo, #expdate, #cvc
            echo '<div class="form-row form-row-wide"><label>Card Number <span class="required">*</span></label>
                <input id="VortexPayment_ccNo" type="text" autocomplete="off">
                </div>
                <div class="form-row form-row-first">
                    <label>Expiry Date <span class="required">*</span></label>
                    <input id="VortexPayment_expdate" type="text" autocomplete="off" placeholder="MM / YY">
                </div>
                <div class="form-row form-row-last">
                    <label>Card Code (CVC) <span class="required">*</span></label>
                    <input id="VortexPayment_cvv" type="password" autocomplete="off" placeholder="CVC">
                </div>
                <div class="clear"></div>';
         
            do_action( 'woocommerce_credit_card_form_end', $this->id );
         
            echo '<div class="clear"></div></fieldset>';
         
        }
 
		/*
		 * Custom CSS and JS, in most cases required only when you decided to go with a custom credit card form
		 */
        public function payment_scripts() {
 
            // we need JavaScript to process a token only on cart/checkout pages, right?
            if ( ! is_cart() && ! is_checkout() && ! isset( $_GET['pay_for_order'] ) ) {
                return;
            }
         
            // if our payment gateway is disabled, we do not have to enqueue JS too
            if ( 'no' === $this->enabled ) {
                return;
            }
         
            // no reason to enqueue JavaScript if API keys are not set
            if ( empty( $this->private_key ) || empty( $this->publishable_key ) ) {
                return;
            }
         
            // do not work with card detailes without SSL unless your website is in a test mode
            if ( ! $this->testmode && ! is_ssl() ) {
                return;
            }
         
            // let's suppose it is our payment processor JavaScript that allows to obtain a token
            wp_enqueue_script( 'VortexPayment_js', 'https://www.VortexPaymentpayments.com/api/token.js' );
         
            // and this is our custom JS in your plugin directory that works with token.js
            wp_register_script( 'woocommerce_VortexPayment', plugins_url( 'VortexPayment.js', __FILE__ ), array( 'jquery', 'VortexPayment_js' ) );
         
            // in most payment processors you have to use PUBLIC KEY to obtain a token
            wp_localize_script( 'woocommerce_VortexPayment', 'VortexPayment_params', array(
                'publishableKey' => $this->publishable_key
            ) );
         
            wp_enqueue_script( 'woocommerce_VortexPayment' );
         
        }
 
		/*
 		 * Fields validation, more in Step 5
		 */
		public function validate_fields(){
 
            if( empty( $_POST[ 'billing_first_name' ]) ) {
                wc_add_notice(  'First name is required!', 'error' );
                return false;
            }
            return true;
         
        }
 
		/*
		 * We're processing the payments here, everything about it is in Step 5
		 */
		public function process_payment( $order_id ) {
 
            global $woocommerce;
         
            // we need it to get any order detailes
            $order = wc_get_order( $order_id );
         
         
            /*
              * Array with parameters for API interaction
              * $args = array('Name');
             */
           
         
            /*
             * Your API interaction could be built with wp_remote_post()
              */
             $response = add_query_arg( 'key', 'value', 'http://example.com' );
         
         
             if( !is_wp_error( $response ) ) {
         
                 $body = json_decode( $response['body'], true );
         
                 // it could be different depending on your payment processor
                 if ( $body['response']['responseCode'] == 'APPROVED' ) {
         
                    // we received the payment
                    $order->payment_complete();
                    $order->reduce_order_stock();
         
                    // some notes to customer (replace true with false to make it private)
                    $order->add_order_note( 'Hey, your order is paid! Thank you!', true );
         
                    // Empty cart
                    $woocommerce->cart->empty_cart();
         
                    // Redirect to the thank you page
                    return array(
                        'result' => 'success',
                        'redirect' => $this->get_return_url( $order )
                    );
         
                 } else {
                    wc_add_notice(  'Please try again.', 'error' );
                    return;
                }
         
            } else {
                wc_add_notice(  'Connection error.', 'error' );
                return;
            }
         
        }
 
		/*
		 * In case you need a webhook, like PayPal IPN etc
		 */
		public function webhook() {
 
            $order = wc_get_order( $_GET['id'] );
            $order->payment_complete();
            $order->reduce_order_stock();
         
            update_option('webhook_debug', $_GET);
        }
 	}
}