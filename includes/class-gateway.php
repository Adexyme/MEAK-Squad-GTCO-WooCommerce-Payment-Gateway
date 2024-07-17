<?php
class My_Custom_Gateway extends WC_Payment_Gateway {
  
  // Constructor method
  public function __construct() {

    $wc_logger = wc_get_logger();

    $this->id                 = 'my_custom_gateway';
    $this->icon = 'https://www.raastid.com/assets/images/logoIcon/light_logo.png'; 
    $this->method_title       = __('MEAK Squad GTCO Custom Gateway', 'my-custom-gateway');
    $this->method_description = __('Accept payments through Squad GTCO numerous Online/Offline Payment Options - Credit Cards, USSD, Bank Transfer, Payment Link, Virtual Account e.t.c. <br>
    <b>Note: </b>Please add the following URL to Your Squad dashboard WebHook Option: <b><i>'.get_site_url().'/wc-api/squad_webhook</i></b>', 'my-custom-gateway');
    
    // Other initialization code goes here
    $this->order_id_prepend = 'MEAK';
    $this->order_id_append = 'G75';


    
    $this->init_form_fields();
    $this->init_settings();

    $this->title = $this->get_option( 'title' );
    $this->description = $this->get_option( 'description' );
    $this->success_url = $this->get_option( 'success_url' );
    $this->cancel_url = $this->get_option( 'cancel_url' );
    $this->logo_url = $this->get_option( 'logo_url' );
    //$this->ipn_url = plugin_dir_url( __FILE__ ) . 'ipn.php';

    $this->testmode = 'yes' === $this->get_option( 'testmode' );
    $this->private_key = $this->get_option( 'private_key' );
    $this->publishable_key = $this->get_option( 'publishable_key' );
    $this->test_private_key = $this->get_option( 'test_private_key' );
    $this->test_publishable_key = $this->get_option( 'test_publishable_key' );  

    $this->public_key = ($this->testmode == 'yes') ? $this->test_publishable_key : $this->publishable_key;
    $this->secret_key = ($this->testmode == 'yes') ? $this->test_private_key : $this->private_key;       
    
    $this->payment_init_url = ($this->testmode == 'yes') ? 'https://sandbox-api-d.squadco.com/transaction/initiate' : 'https://api-d.squadco.com/transaction/initiate';

    $this->payment_verification_url_stub = 'https://sandbox-api-d.squadco.com/transaction/verify/';

    if( empty($this->success_url) || $this->success_url == null ){
        $this->success_url = get_site_url();
    }

    if( empty($this->cancel_url) || $this->cancel_url == null ){
        $this->cancel_url = get_site_url();
    }

    if( empty($this->logo_url) || $this->logo_url == null ){
        $this->logo_url = 'https://www.raastid.com/assets/images/logoIcon/light_logo.png';
    }
    
    add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

    // Register a callback url to be called immediately payment is successful, allowing to navigate back to the app for appropriate notification and processing.
    add_action( 'woocommerce_api_squad_success_callback', array( $this, 'success_callback_url' ) );   
    
    // Register a webhook for payment success notification - this can be called by the Pay Proccessor any time and may be done more than once after payment
    //add_action( 'woocommerce_api_squad_webhook', array( $this, 'webhook' ) );

  }
  
  public function init_form_fields() {
   
    $this->form_fields = array(
      'enabled' => array(
        'title'   => __('Enable/Disable', 'my-custom-gateway'),
        'type'    => 'checkbox',
        'label'   => __('Enable My Custom Gateway', 'my-custom-gateway'),
        'default' => 'yes',
      ),
      'title' => array(
                    'title'       => __('Title', 'my-custom-gateway'),
                    'type'        => 'text',
                    'description' => __('This controls the title which the user sees during checkout.', 'my-custom-gateway'),
                    'default'     => 'Squad',
                    'desc_tip'    => true,
                ),
                'description' => array(
                    'title'       => __('Description'),
                    'type'        => 'textarea',
                    'description' => __('This controls the description which the user sees during checkout.', 'my-custom-gateway'),
                    'default'     => 'Make payment using any of Squad multiple payment channels',
                ),
                'testmode' => array(
                    'title'       => 'Test mode',
                    'label'       => 'Enable Test Mode',
                    'type'        => 'checkbox',
                    'description' => 'Tick this to enable sandbox/test mode. According to Squad\'s documentaion, test Card details are: ',
                    'default'     => 'yes',
                ),
                'success_url' => array(
                    'title'       => __('Success URL'),
                    'description' => __('This is the URL where Squad will redirect after payment was SUCCESSFUL. If you leave this field empty, it will be redirected to your site. Example: https://www.example.com/', 'my-custom-gateway'),
                    'type'        => 'text',
                    'default'     => get_site_url().'/wc-api/squad_success_callback'
                ),
                'cancel_url' => array(
                    'title'       => __('Cancel URL'),
                    'description' => __('This is the URL where Squad will redirect if payment was CANCELLED. If you leave this field empty, it will be redirected to your site. Example: https://www.example.com/', 'my-custom-gateway'),
                    'type'        => 'text',
                    'default'     => ''
                ),
                'logo_url' => array(
                    'title'       => __('Logo Url'),
                    'description' => __('Your Site Logo URL. If you leave this field empty, it will use Squad icon.  Example: https://www.example.com/image.png', 'my-custom-gateway'),
                    'type'        => 'text',
                    'default'     => ''
                ),
                'publishable_key' => array(
                    'title'       => __('Public Key'),
                    'description' => __('Your Squad Public Key', 'my-custom-gateway'), 
                    'type'        => 'text',
                    'default'     => ''
                ),
                'private_key' => array(
                    'title'       => __('Private Key'),
                    'description' => __('Your Squad Private Key', 'my-custom-gateway'),
                    'type'        => 'password',
                    'default'     => ''
                ),
                'test_publishable_key' => array(
                    'title'       => __('Public test Key'),
                    'description' => __('Your Squad Public test Key', 'my-custom-gateway'),
                    'type'        => 'text',
                    'default'     => ''
                ),
                'test_private_key' => array(
                    'title'       => __('Private Key'),
                    'description' => __('Your Squad Private test Key', 'my-custom-gateway'),
                    'type'        => 'password',
                    'default'     => ''
                )
      // Add more settings fields as needed
    );
  }
  
  // Process the payment
  public function process_payment($order_id) {
    //global $woocommerce;

    $wc_logger = wc_get_logger();

    $wc_logger->debug( 'inside process_payment', array( 'source' => 'MAAK Debug' ) );

    $order        = wc_get_order( $order_id );
    $amount       = $order->get_total() * 100;  

    //$wc_logger->debug( 'debug tracker : made request', array( 'source' => 'MAAK Debug' ) );

    //$wc_logger->debug( 'ret currency : '.$order->get_currency().'made request', array( 'source' => 'MAAK Debug' ) );
    
    //if($order->get_currency() != 'NGN' || $order->get_currency() != 'USD') die('Your currency is not supported by Squad - maak_payment_gateway/process_payment') ;

    $wc_logger->debug( 'debug tracker2 : made request', array( 'source' => 'MAAK Debug' ) );
    $squad_payment_params = array(
        'amount'       => absint( $amount ),
        'email'        => $order->get_billing_email(),
        'currency'     => $order->get_currency(),
        "initiate_type"=> "inline",
        'transaction_ref'    => $this->order_id_prepend.'-'.$order_id.'-'.$this->order_id_append,
        'callback_url' => $this->get_option( 'success_url' ),
    );
    //$wc_logger->debug( 'debug tracker3 : made request', array( 'source' => 'MAAK Debug' ) );


    $headers = array(
        'Authorization' => 'Bearer ' . $this->secret_key,
        'Content-Type'  => 'application/json'
    );

    $args = array(
        'headers' => $headers,
        'timeout' => 60,
        'body'    => json_encode( $squad_payment_params )
    );           

  $squad_payment_init_url = $this->payment_init_url;

  $wc_logger->debug( 'inside process_payment x : payload'.print_r( $args, true ) , array( 'source' => 'MAAK Debug' ) );

  $wc_logger->debug( 'inside process_payment y : url: '. $squad_payment_init_url  , array( 'source' => 'MAAK Debug' ) );

    $request = wp_remote_post( $squad_payment_init_url , $args );

   // $wc_logger->debug( 'debug tracker4 : error code:'.wp_remote_retrieve_response_code( $request ), array( 'source' => 'MAAK Debug' ) );
    
    $wc_logger->debug( 'inside process_payment 2 : request body'.print_r( $request, true ) , array( 'source' => 'MAAK Debug' ) );

if ( ! is_wp_error( $request ) && 200 === wp_remote_retrieve_response_code( $request ) ) {

$squad_response = json_decode( wp_remote_retrieve_body( $request ) );
$wc_logger->debug( 'inside process_payment 3 : request succeed' , array( 'source' => 'MAAK Debug' ));
return array(
'result'   => 'success',
'redirect' => $squad_response->data->checkout_url,
);

} else {
    $wc_logger->debug( 'inside process_payment 3 : request failed', array( 'source' => 'MAAK Debug' ) );

    wp_redirect( $this->get_return_url( $order ) );

//return;

}

  }


  public function verify_payment($order_id){

    $verification_url = $this->payment_verification_url_stub.$this->order_id_prepend.'-'.$order_id.'-'.$this->order_id_append;

    $wc_logger = wc_get_logger();

    $wc_logger->debug( 'Responding To Payment Verification call', array( 'source' => 'MEAK Verify Payment Debug' ) );
    $wc_logger->debug( 'url: '.$verification_url , array( 'source' => 'MEAK Verify Payment Debug' ) );

    $headers = array(
        'Authorization' => 'Bearer ' . $this->secret_key,
        'Content-Type'  => 'application/json'
    );

    $args = array(
        'headers' => $headers,
        'timeout' => 60
    );  
    $request = wp_remote_get( $verification_url , $args );

      $wc_logger->debug( 'Responding To Payment Verification call : ret payload'.print_r( $request, true ) , array( 'source' => 'MAAK Verify Payment Payload' ) );

    if ( ! is_wp_error( $request ) && 200 === wp_remote_retrieve_response_code( $request ) ) {

        $squad_response = json_decode( wp_remote_retrieve_body( $request ) );
        $wc_logger->debug( 'inside verify_payment 1 : request succeed' , array( 'source' => 'MAAK Verify Payment Debug' ));

        if($squad_response->status == 200 && strtolower($squad_response->data->transaction_status) == 'success'){
            $squad_response = json_decode( wp_remote_retrieve_body( $request ) );

            $wc_logger->debug( 'inside verify_payment  : request succeed 2' , array( 'source' => 'MAAK Verify Payment Debug' ));

          return array(
            'status' => true,
            'amount_paid'  => $squad_response->data->transaction_amount,
            'currency_symbol' => $squad_response->data->transaction_currency_id
        );

        }
        else{

            $squad_response = json_decode( wp_remote_retrieve_body( $request ) );
            
            $wc_logger->debug( 'inside verify_payment else : request failed 2', array( 'source' => 'MAAK Verify Payment Debug' ) );
            
          return array(
            'status' => false,
            'amount_paid'  => '',
            'currency_symbol' => ''
        );

        }
        
        } else {
            $wc_logger->debug( 'inside verify_payment else : request failed 1', array( 'source' => 'MAAK Verify Payment Debug' ) );
        
        return array(
            'status' => false,
            'amount_paid'  => '',
            'currency_symbol' => ''
        );

        }


  }




  public function webhookX() {

    $wc_logger = wc_get_logger();

    $wc_logger->debug( 'Responding To Payment WebHook', array( 'source' => 'MEAK WebHook Debug' ) );
    $wc_logger->debug( 'reference: '.$_GET[ 'reference' ] , array( 'source' => 'MEAK WebHook Debug' ) );
    $orderid = explode('-',$_GET[ 'reference' ]);

    $order = wc_get_order( $orderid[1 ] );
	$order->payment_complete();
	$order->reduce_order_stock();
    wp_redirect( $this->get_return_url( $order ) );

  }

  public function success_callback_url() {

    $wc_logger = wc_get_logger();

    $wc_logger->debug( 'Responding To Payment Success URL', array( 'source' => 'MEAK Success Callback Debug' ) );
    $wc_logger->debug( 'reference: '.$_GET[ 'reference' ] , array( 'source' => 'MEAK Success Callback Debug' ) );
    $orderid = explode('-',$_GET[ 'reference' ]);



    $order = wc_get_order( $orderid[1] );
    //if payment verification failed, halt and redirect
    $verify = $this->verify_payment($orderid[1]);
    if(!$verify['status']) $this->get_return_url( $order );

    $order_currency = method_exists($order, 'get_currency') ? $order->get_currency() : $order->get_order_currency();
    $currency_symbol = get_woocommerce_currency_symbol($order_currency);

    if($order->get_total() > (floatval($verify['amount_paid'])/100) || $verify['currency_symbol'] != $currency_symbol){

        $order->update_status('on-hold', '');
        $order->reduce_order_stock();     
        wp_redirect( $this->get_return_url( $order ) );
    }

	$order->payment_complete();
	$order->reduce_order_stock();
    wp_redirect( $this->get_return_url( $order ) );

  }
  
}
?>