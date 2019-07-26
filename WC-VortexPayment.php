<?php
/*
 * Plugin Name: WooCommerce Vortex Payment Gateway
 * Plugin URI: http://vortex-solutions.com.mx/
 * Description: Recibe pagos con tarjeta en tu sitio web con Vortex Payement.
 * Author: Jesus Torres
 * Author URI: http://vortex-solutions.com.mx/
 * Version: 1.0.0
 */
/*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter( 'woocommerce_payment_gateways', 'vortex_add_gateway_class' );
function vortex_add_gateway_class( $gateways ) {
	$gateways[] = 'WC_Vortex_Gateway'; // your class name is here
	return $gateways;
}
 
/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */
add_action( 'plugins_loaded', 'vortex_init_gateway_class' );
function vortex_init_gateway_class() {
 
	class WC_Vortex_Gateway extends WC_Payment_Gateway {
 
 		/**
 		 * Class constructor, more about it in Step 3
 		 */
 		public function __construct() {
 
            $this->id = 'vortex'; // payment gateway plugin ID
            $this->icon = '../vortex-payment/media/VortexPaymentLogo.png'; // URL of the icon that will be displayed on checkout page near your gateway name
            $this->method_title = 'Vortex Payment Gateway';
            $this->method_description = 'Recibe pagos con tarjeta en tu sitio web con Vortex Payement'; // will be displayed on the options page
         
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
			$this->BusinessID = $this->get_option('BID');

            // This action hook saves the settings
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
         
            
         
            // You can also register a webhook here
            // add_action( 'woocommerce_api_{webhook name}', array( $this, 'webhook' ) );
 
 		}
 
		/**
 		 * Plugin options, we deal with it in Step 3 too
 		 */
 		public function init_form_fields(){
 
		$this->form_fields = array(
			'enabled' => array(
				'title'       => 'Enable/Disable',
				'label'       => 'Enable Vortex Gateway',
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no'
			),
			'title' => array(
				'title'       => 'Title',
				'type'        => 'text',
				'description' => 'This controls the title which the user sees during checkout.',
				'default'     => 'Vortex Payment',
				'desc_tip'    => true,
			),
			'description' => array(
				'title'       => 'Description',
				'type'        => 'textarea',
				'description' => 'This controls the description which the user sees during checkout.',
				'default'     => 'Paga con Vortex Payment.',
			),
			'BID' => array(
				'title'       => 'Business ID',
				'type'        => 'text'
			)

			);
 
	 	}
 
		/**
		 * You will need it if you want your custom credit card form, Step 4 is about it
		 */
		public function payment_fields() {
 
		
 
		}
 
		/*
		 * Custom CSS and JS, in most cases required only when you decided to go with a custom credit card form
		 */
	 	public function payment_scripts() {
 
		
 
	 	}
 
		/*
 		 * Fields validation, more in Step 5
		 */
		public function validate_fields() {
 
		
 
		}
 
		/*
		 * We're processing the payments here, everything about it is in Step 5
		 */
		public function process_payment( $order_id ) {
			
			global $woocommerce;
 
			// we need it to get any order detailes
			$order = wc_get_order( $order_id );

			//Variables para API
			$url1 = 'https://qaag.mitec.com.mx/praga-ws/url/generateUrlV3';
            $authorization = "Authorization: Bearer MDhhNzllMjMtNTU1Yy00ZDc3LThmZDctODEyNmVkZTFmODhi";
            $Key="E166173C2B870BDC3F62A67A77442FE1";
			$fecha=date("d/m/Y");
			
			
			/*
			* Array with parameters for API interaction
			*/
			$data = array(
				'ammount' => $order->get_total(),
				'businessId' => $this->BusinessID,
				'currency' => 'MXN',
				'effectiveDate' => $fecha,
				'id' => $order_id,
				'paymentTypes' => '401',
				'reference' => 'Orden'.$order_id,
				'station' => 'prueba',
				'userCode' => '1563322091989'
			);
			
			$payload = json_encode($data);
			
			$res = encriptar($payload,$Key);

			$ch = curl_init($url1);
			
			curl_setopt($ch, CURLOPT_POSTFIELDS, $res);

            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json', $authorization));

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $result = curl_exec($ch);
			curl_close($ch);
			$respuesta = json_decode($result);
			$pag=$respuesta->url;
			
			return array(
				'result' => 'success',
				'redirect' => $respuesta->url
			);
			
 
		
 
		 }
		
		 
		

		
		 


	 }
	 function encriptar($plaintext, $key128){
		$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-128-cbc'));
		$cipherText = openssl_encrypt ( $plaintext, 'AES-128-CBC', hex2bin($key128), 1, $iv);
		return base64_encode($iv.$cipherText);
	}
	 
}
