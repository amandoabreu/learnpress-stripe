<?php
/**
 * Plugin load class.
 *
 * @author   AmandoAbreu
 * @package  LearnPressStripeGateway/Classes
 * @version  5.1.0
 */

// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LearnPressStripeGateway' ) ) {
    /**
     * Class LearnPressStripeGateway
     */
    class LearnPressStripeGateway extends LP_Addon {

        /**
         * @var string
         */
        public $version = LP_ADDON_STRIPE_GATEWAY_VER;

        /**
         * @var string
         */
        public $require_version = LP_ADDON_STRIPE_GATEWAY_REQUIRE_VER;

        /**
         * LP_Addon_IDPay_Payment constructor.
         */
        public function __construct() {
            add_action( 'init', [ $this, 'check_webhook_stripe' ] );
            add_action( 'init', [ $this, 'check_order_cancelled' ] );
            parent::__construct();
        }

        /**
         * Define Learnpress IDPay payment constants.
         *
         */
        protected function _define_constants() {
            define( 'LP_ADDON_STRIPE_GATEWAY_PATH', dirname( LP_ADDON_STRIPE_GATEWAY_FILE ) );
            define( 'LP_ADDON_STRIPE_GATEWAY_INC', LP_ADDON_STRIPE_GATEWAY_PATH . '/inc/' );
            define( 'LP_ADDON_STRIPE_GATEWAY_URL', plugin_dir_url( LP_ADDON_STRIPE_GATEWAY_FILE ) );
            define( 'LP_ADDON_STRIPE_GATEWAY_TEMPLATE', LP_ADDON_STRIPE_GATEWAY_PATH . '/templates/' );
        }

        /**
         * Include required core files used in admin and on the frontend.
         *
         */
        protected function _includes() {
            include_once LP_ADDON_STRIPE_GATEWAY_INC . 'class-lp-gateway-stripe.php';
        }

        /**
         * Init hooks.
         */
        protected function _init_hooks() {
            // add payment gateway class
            add_filter( 'learn_press_payment_method', array( $this, 'add_payment' ) );
            add_filter( 'learn-press/payment-methods', array( $this, 'add_payment' ) );

            // override order received message
            //add_filter( 'learn-press/order/received-order-message', array( $this, 'received_message'));
        }

        public function received_message(){
            return "Thank you. Your order has been received. View and manage orders on your profile";
        }

        /**
         * Enqueue assets.
         *
         */
        protected function _enqueue_assets() {

            if (LP()->settings->get( 'stripe.enable' ) == 'yes' ) {
                $user = learn_press_get_current_user();


                // if enabled
            }

            $user = learn_press_get_current_user();


            wp_enqueue_script( 'learnpress-stripe-checkout-gateway', $this->get_plugin_url( 'assets/js/script.js' ), array() );
            wp_enqueue_style( 'learnpress-stripe-checkout-gateway', $this->get_plugin_url( 'assets/css/style.css' ), array() );

            $data = array(
                'plugin_url'  => plugins_url( '', LP_ADDON_STRIPE_GATEWAY_FILE )
            );
            wp_localize_script( 'learn-press-stripe-checkout', 'learn_press_stripe_scheckout', $data );
        }

        function check_order_cancelled(){
            if(!isset($_GET['cancel-order'])){
                return;
            }
            $order_id = $_GET['cancel-order'];

            $order = learn_press_get_order($order_id);

            // Check that the order exists
            if (!$order) {
                throw new Exception(__('Order not found', 'learnpress-stripe'));
            }

            // do something if order is cancelled
            // error_log("PPPP");
            if(defined(LP_ORDER_CANCELLED)) {
                $order->update_status(LP_ORDER_CANCELLED);
            } else {
                $order->update_status("cancelled");
            }
        }

        function check_webhook_stripe() {
            if(!isset($_GET['order_id'])){
                return;
            }

            // if order_id is set, validate_ipn and mark it as paid
            $key = $_GET['order_id'];

            $stripe = New LP_Gateway_Stripe();
            $stripe->validate_ipn($key);
        }

        /**
         * Add Stripe to payment system.
         *
         * @param $methods
         *
         * @return mixed
         */
        public function add_payment( $methods )
        {
            $methods['stripe'] = 'LP_Gateway_Stripe';

            return $methods;
        }

        /**
         * Plugin links.
         *
         * @return array
         */
        public function plugin_links()
        {
            $links[] = '<a href="' . admin_url( 'admin.php?page=learn-press-settings&tab=payments&section=stripe' ) . '">' . __( 'Settings', 'learnpress-idpay' ) . '</a>';

            return $links;
        }
    }
}