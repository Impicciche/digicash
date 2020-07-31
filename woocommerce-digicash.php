<?php
/*
 * Plugin Name: WooCommerce Digicash
 * Plugin URI: https://www.impicciche.eu/
 * Description: Payment with Digicash.
 * Author: Giuseppe Impiccichè
 * Version: 1.0.1
 *
 * /


 
/*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
require_once(__DIR__ . "/mobile-detect/Mobile_Detect.php");

/*===========================================================================*/
/*===========================================================================*/
/*===========================================================================*/
/*
set MERCHAND_ID with the MERCHAND_ID received from digicash

QR_URL is the url for the qr code generator

DIGICASH_APPLICATIONS is the url with all the link to the mobile applications for
                      pay by mobile devices

DIGICASH_CALLBACK set the url callback for process the payment

*/
/*===========================================================================*/
/*===========================================================================*/
/*===========================================================================*/
 define("MERCHAND_ID","");
 define("QR_URL","https://pos.digica.sh/qrcode/generator");
 define("DIGICASH_APPLICATIONS","https://static.digica.sh/resources/apps-ttl.json");
 define("DIGICASH_CALLBACK", site_url("/") . "?wc-api=WC_Gateway_Digicash");


add_filter( 'woocommerce_payment_gateways', 'digicash_add_gateway_class' );
function digicash_add_gateway_class( $gateways ) {
	$gateways[] = 'WC_Digicash_Gateway'; // your class name is here
	return $gateways;
}
 
/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */
add_action( 'plugins_loaded', 'digicash_init_gateway_class' );
function digicash_init_gateway_class() {
 
	class WC_Digicash_Gateway extends WC_Payment_Gateway {
        private $payment_device;
 		/**
 		 * Class constructor, more about it in Step 3
 		 */
 		public function __construct() {
             add_action( 'woocommerce_api_wc_gateway_digicash', [$this,"payment_confirmation"] );

             
 
            $this->id = 'digicash'; // payment gateway plugin ID
            $this->icon = '/wp-content/plugins/digicash/images/payconiq_logo.svg'; // URL of the icon that will be displayed on checkout page near your gateway name
            $this->has_fields = true; // in case you need a custom digicash form
            $this->method_title = 'Digicash Gateway';
            $this->method_description = 'Description of Digicash payment gateway'; // will be displayed on the options page
         
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
         
            // This action hook saves the settings
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
         
            // We need custom JavaScript to obtain a token
            add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
         
 
 		}
 
		/**
 		 * Plugin options, we deal with it in Step 3 too
 		 */
 		public function init_form_fields(){
 
            $this->form_fields = array(
                'enabled' => array(
                    'title'       => 'Enable/Disable',
                    'label'       => 'Enable Digicash Gateway',
                    'type'        => 'checkbox',
                    'description' => '',
                    'default'     => 'no'
                ),
                'title' => array(
                    'title'       => 'Title',
                    'type'        => 'text',
                    'description' => 'This controls the title which the user sees during checkout.',
                    'default'     => 'Digicash',
                    'desc_tip'    => true,
                ),
                'description' => array(
                    'title'       => 'Description',
                    'type'        => 'textarea',
                    'description' => 'This controls the description which the user sees during checkout.',
                    'default'     => 'Pay with your digicash via our super-cool payment gateway.',
                ),
                'testmode' => array(
                    'title'       => 'Test mode',
                    'label'       => 'Enable test mode',
                    'type'        => 'checkbox',
                    'description' => 'Place the payment gateway in test mode using test API keys.',
                    'default'     => 'yes',
                    'desc_tip'    => true,
                ),
            );
 
	 	}
 
		/**
		 * You will need it if you want your custom digicash form, Step 4 is about it
		 */
		public function payment_fields() {
 
		// ok, let's display some description before the payment form
        if ( $this->description ) {
            // you can instructions for test mode, I mean test card numbers etc.
            if ( $this->testmode ) {
                $this->description .= ' TEST MODE ENABLED.';
                $this->description  = trim( $this->description );
            }
            // display the description with <p> tags etc.
            echo wpautop( wp_kses_post( $this->description ) );
        }
       
    
 
		}
 
		/*
		 * Custom CSS and JS, in most cases required only when you decided to go with a custom digicash form
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
                wp_enqueue_script( 'digicash_js', plugin_dir_url( "/js/main.js" ) );
            
                // and this is our custom JS in your plugin directory that works with token.js
                wp_register_script( 'woocommerce_digicash', plugins_url( 'digicash.js', __FILE__ ), array( 'jquery', 'digicash_js' ) );
            
                // in most payment processors you have to use PUBLIC KEY to obtain a token
                wp_localize_script( 'woocommerce_digicash', 'digicash_params', array(
                    'publishableKey' => $this->publishable_key
                ) );
            
                wp_enqueue_script( 'woocommerce_digicash' );
 
	 	}
 
		/*
 		 * Fields validation, more in Step 5
		 */
		public function validate_fields() {
 
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
            
            if(isset($_POST)){
                if(isset($_POST["digicash_this_device"])&&!empty($_POST["digicash_this_device"]))
                update_field("payment_device",$_POST["digicash_this_device"],$order_id);
            }
            /*
              * Array with parameters for API interaction
             */
            $args = array(
         
                /** .... */
         
            );
            
            /*
             * Your API interaction could be built with wp_remote_post()
              */
              $ch = curl_init();
              curl_setopt_array($ch,[
                    'CURLOPT_URL' =>  QR_URL . "?merchantId=MERCHAND_ID&amount=" . $order->get_total() . "&transactionReference=" . $order->get_id(),
                    'CURLOPT_RETURNTRANSFER' => true,
              ]);
              $response = curl_exec($ch);
              curl_close($ch);
              
              print_r(json_decode($response));
                  return array(
                    'result' => 'success',
                    'redirect' => $this->get_return_url( $order )
                );
             $response = wp_remote_post( '{payment processor endpoint}', $args );
         
         
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
 

         public function payment_confirmation(){
            if(isset($_GET)&&!empty($_GET)){
                $valid_request = isset($_GET["operation"]) && !empty($_GET["operation"]) && $_GET["operation"] == "VALIDATE";
                if($valid_request){
                    $valid_request &= isset($_GET["transactionReference"]) && !empty($_GET["transactionReference"]) && is_numeric($_GET["transactionReference"]);
                    $valid_request &= isset($_GET["amount"]) && !empty($_GET["amount"]);
                    $valid_request &= isset($_GET["transactionId"]) && !empty($_GET["transactionId"]);

                    $order = ($valid_request)?wc_get_order( sanitize_text_field( $_GET["transactionReference"] ) ):0;
                    if($valid_request && $order){
                        $amout = sanitize_text_field( $_GET["amount"] );
                        $transactionId = sanitize_text_field( $_GET["transactionId"] );
                        $order_total = $order->get_total()*100;
                        if($order_total==$_GET["amount"]&&!$order->is_paid()){
                            update_field("transactionid",$transactionId,$order->get_id());
                            echo 'ok';
                            exit;
                        }
                    }
                    
                }else{
                    $valid_request = isset($_GET["operation"]) && !empty($_GET["operation"]) && $_GET["operation"] == "CONFIRM";
                    $valid_request &= isset($_GET["transactionReference"]) && !empty($_GET["transactionReference"]) && is_numeric($_GET["transactionReference"]);
                    $valid_request &= isset($_GET["amount"]) && !empty($_GET["amount"]);
                    $valid_request &= isset($_GET["transactionId"]) && !empty($_GET["transactionId"]);

                    $order = ($valid_request)?wc_get_order( sanitize_text_field( $_GET["transactionReference"] ) ):0;

                    if($valid_request && $order){
                        $amout = sanitize_text_field( $_GET["amount"] );
                        $transactionId = sanitize_text_field( $_GET["transactionId"] );

                        $wp_transaction_id = get_field("transactionid",$order->get_id());
                        $order_total = $order->get_total()*100;


                        if($order_total==$_GET["amount"] && $transactionId==$wp_transaction_id){
                            $order->payment_complete($transactionId);
                            exit;
                        }
                    }
                }
                echo "nok reason";
                exit;
            }


            exit();
         }
 	}
}