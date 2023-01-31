<?php
/**
 * Class Stripe Payment gateway.
 *
 * @author  AmandoAbreu
 * @package LearnPressStripeGateway/Classes
 * @since   3.0.0
 * @version 5.0.1
 */
include_once(plugin_dir_path( __FILE__ ) . '/stripe/init.php');
/**
 * Prevent loading this file directly
 */
class LP_Gateway_Stripe extends LP_Gateway_Abstract
{

    private $test_secret_key = '';
    /**
     * @var array|string|null
     */
    private $test_mode;
    /**
     * @var array|string|null
     */
    private $live_secret_key;
    /**
     * @var array|string|null
     */
    private $test_publishable_key;
    /**
     * @var array|string|null
     */
    private $live_publishable_key;

    /**
     * Constructor for the gateway.
     */
    public function __construct()
    {
        $this->id = 'stripe';
        $this->icon = plugin_dir_url(__FILE__).'../assets/images/stripe.png';
        $this->method_title = __('Stripe', 'learnpress-stripe');
        $this->method_description = __('Accept payments via Stripe.', 'learnpress-stripe');

        // Load the settings.
        //$this->init_form_fields();

        // Define user set variables
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->test_mode = $this->get_option('test_mode');
        $this->test_secret_key = $this->get_option('test_secret_key');
        $this->test_publishable_key = $this->get_option('test_publishable_key');
        $this->live_secret_key = $this->get_option('live_secret_key');
        $this->live_publishable_key = $this->get_option('live_publishable_key');

        // Actions
        add_action('learn_press_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        add_action( 'init', [ $this, 'check_webhook_stripe_ipn' ] );

        // Payment listener/API hook
        add_action('learn_press_stripe_ipn_handler', array($this, 'process_payment'));

        parent::__construct();
    }

    /**
     * Initialise Gateway Settings Form Fields
     */
    public function get_settings()
    {

        return apply_filters(
            'learn-press/gateway-payment/stripe/settings',
            array(
                array(
                    'type' => 'title',
                ),
                array(
                    'title' => esc_html__( 'Enable/Disable', 'learnpress-stripe' ),
                    'id' => '[enable]',
                    'default' => 'yes',
                    'type' => 'checkbox',
                    'desc' => esc_html__( 'Enable Stripe Payment', 'learnpress-stripe' ),
                ),
                array(
                    'title' => esc_html__( 'Title', 'learnpress-stripe' ),
                    'id' => '[title]',
                    'type' => 'text',
                    'desc' => esc_html__( 'This controls the title which the user sees during checkout.', 'learnpress-stripe' ),
                ),
                array(
                    'title' => esc_html__( 'Description', 'learnpress-stripe' ),
                    'id' => '[description]',
                    'type' => 'textarea',
                    'desc' => esc_html__( 'This controls the description which the user sees during checkout.', 'learnpress-stripe' ),
                ),
                array(
                    'title' => esc_html__( 'Test Mode', 'learnpress-stripe' ),
                    'id' => '[test_mode]',
                    'default' => 'yes',
                    'type' => 'checkbox',
                    'desc' => esc_html__( 'Enable Test Mode (uses your test keys)', 'learnpress-stripe' ),
                ),
                array(
                    'title' => esc_html__( 'Test Secret Key', 'learnpress-stripe' ),
                    'id' => '[test_secret_key]',
                    'type' => 'text',
                    'desc' => sprintf(__('Get your API keys from your <a href="%s" target="_blank">Stripe account</a>.', 'learnpress-stripe'), 'https://dashboard.stripe.com/account/apikeys'),
                ),
                array(
                    'title' => __('Test Publishable Key', 'learnpress-stripe'),
                    'type' => 'text',
                    'id' => '[test_publishable_key]',
                    'default' => '',
                    'desc' => sprintf(__('Get your API keys from your <a href="%s" target="_blank">Stripe account</a>.', 'learnpress-stripe'), 'https://dashboard.stripe.com/account/apikeys'),
                ),
                array(
                    'title' => __('Live Secret Key', 'learnpress-stripe'),
                    'type' => 'text',
                    'id' => '[live_secret_key]',
                    'default' => '',
                    'desc' => sprintf(__('Get your API keys from your <a href="%s" target="_blank">Stripe account</a>.', 'learnpress-stripe'), 'https://dashboard.stripe.com/account/apikeys'),
                ),
                array(
                    'title' => __('Live Publishable Key', 'learnpress-stripe'),
                    'type' => 'text',
                    'id' => '[live_publishable_key]',
                    'default' => '',
                    'desc' => sprintf(__('Get your API keys from your <a href="%s" target="_blank">Stripe account</a>.', 'learnpress-stripe'), 'https://dashboard.stripe.com/account/apikeys'),
                ),
                array(
                    'title'   => esc_html__( 'Order success page', 'learnpress-stripe' ),
                    'id'      => '[order_success_page_id]',
                    'default' => '',
                    'type'    => 'pages-dropdown',
                    'desc'    => 'The page users get sent to after successfully paying for an order'
                ),
                array(
                    'title'   => esc_html__( 'Order cancelled page', 'learnpress-stripe' ),
                    'id'      => '[order_cancelled_page_id]',
                    'default' => '',
                    'type'    => 'pages-dropdown',
                    'desc'    => 'Overrides the default payment cancelled page by LearnPress only for stripe payments'
                ),
                array(
                    'type' => 'sectionend',
                ),

            )
        );

    }

    /**
     * Process the payment and return the result
     *
     * @param int $order_id
     * @return array
     */
    public function process_payment($order_id){
        // Get the order
        $order = learn_press_get_order($order_id);

        // Include the Stripe library
        //require_once LP_PLUGIN_PATH . '/inc/gateways/stripe/lib/Stripe.php';

        // Get the API keys
        $secret_key = $this->settings->get('test_mode') == 'yes' ? $this->settings->get('test_secret_key') : $this->settings->get('live_secret_key');
        $publishable_key = $this->settings->get('test_mode') == 'yes' ? $this->settings->get('test_publishable_key') : $this->settings->get('live_publishable_key');

        $stripe = new \Stripe\StripeClient($secret_key);
        try {
            $checkout_session = $stripe->checkout->sessions->create([
                'line_items' => [[
                    'price_data' => [
                        'currency' => learn_press_get_currency(),
                        'product_data' => [
                            'name' => sprintf(__('Payment for order %s', 'learnpress-stripe'), $order->get_order_number())
                        ],
                        'unit_amount' => $order->get_total() * 100,
                    ],
                    'quantity' => 1,
                ]],
                'client_reference_id' => $order->get_id(),
                'mode' => 'payment',
                'success_url' => $this->settings->get("order_success_page_id") ? get_permalink($this->settings->get("order_success_page_id")) . '?order_id='.$order->get_id() : $this->get_return_url($order) . '&order_id='.$order->get_id(),
                'cancel_url' => $this->settings->get("order_cancelled_page_id") ? get_permalink($this->settings->get("order_cancelled_page_id")) . '?cancel-order='.$order->get_id() : $order->get_cancel_order_url(),
            ]);

            return array(
                'result' => 'success',
                'redirect' => $checkout_session->url
            );


        } catch (Exception $e) {

            // Payment failed
            error_log($e->getMessage());
            $order->add_note(sprintf(__('Stripe checkout failed. Error: %s', 'learnpress-stripe'), $e->getMessage()));

            learn_press_add_message($e->getMessage(), 'error');

            return array(
                'result' => 'failed',
                'redirect' => $this->get_return_url($order)
            );

        }
    }

    public function mark_completed($order){

    }

    /**
     * Validate the IPN
     *
     * @param string $transaction_id
     * @return bool
     */
    public function validate_ipn($id) {
        try {

            // Get the order ID
            $order_id = $id;

            // Get the order
            $order = learn_press_get_order($order_id);

            // Check that the order exists
            if (!$order) {
                throw new Exception(__('Order not found', 'learnpress-stripe'));
            }

            $order->payment_complete();
            return true;
        } catch (Exception $e) {
            // Log the error
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * Process the payment and return the result
     *
     * @param int $order_id
     * @return array
     */
    public function process_payment_old($order_id)
    {

        // Get the order
        $order = learn_press_get_order($order_id);

        // Include the Stripe library
        //require_once LP_PLUGIN_PATH . '/inc/gateways/stripe/lib/Stripe.php';

        // Get the API keys
        $secret_key = $this->test_mode == 'yes' ? $this->test_secret_key : $this->live_secret_key;
        $publishable_key = $this->test_mode == 'yes' ? $this->test_publishable_key : $this->live_publishable_key;

        // Set the API key
        \Stripe\Stripe::setApiKey($secret_key);

        // Get the credit card details submitted by the form
        $token = $_POST['stripeToken'];

        // Create a charge

        try {
            $charge = \Stripe\Charge::create(array(
                'amount' => $order->get_total() * 100,
                'currency' => learn_press_get_currency(),
                'source' => $token,
                'description' => sprintf(__('Payment for order %s', 'learnpress-stripe'), $order->get_order_number())
            ));

            // Payment complete
            $order->payment_complete();

            // Add order note
            $order->add_note(sprintf(__('Stripe payment completed. Transaction ID: %s', 'learnpress-stripe'), $charge->id));

            // Remove cart
            //learn_press_empty_cart();

            // Return thank you page redirect
            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url($order)
            );

        } catch (Stripe\Exception\CardException $e) {

            // Payment failed
            $body = $e->getJsonBody();
            $err = $body['error'];

            $order->add_note(sprintf(__('Stripe payment failed. Error: %s', 'learnpress-stripe'), $err['message']));

            learn_press_add_message($err['message'], 'error');

            return array(
                'result' => 'failed',
            );

        } catch (\Stripe\Exception\InvalidRequestException $e) {

            // Payment failed
            $body = $e->getJsonBody();
            $err = $body['error'];

            $order->add_note(sprintf(__('Stripe payment failed. Error: %s', 'learnpress-stripe'), $err['message']));

            learn_press_add_message($err['message'], 'error');

            return array(
                'result' => 'failed',
            );

        } catch (Stripe\Exception\ApiConnectionException $e) {

            // Payment failed
            $body = $e->getJsonBody();
            $err = $body['error'];

            $order->add_note(sprintf(__('Stripe payment failed. Error: %s', 'learnpress-stripe'), $err['message']));

            learn_press_add_message($err['message'], 'error');

            return array(
                'result' => 'failed',
            );

        } catch (\Stripe\Exception\UnknownApiErrorException $e) {

            // Payment failed
            $body = $e->getJsonBody();
            $err = $body['error'];

            $order->add_note(sprintf(__('Stripe payment failed. Error: %s', 'learnpress-stripe'), $err['message']));

            learn_press_add_message($err['message'], 'error');

            return array(
                'result' => 'failed',
            );

        } catch (Exception $e) {

            // Payment failed
            $order->add_note(sprintf(__('Stripe payment failed. Error: %s', 'learnpress-stripe'), $e->getMessage()));

            learn_press_add_message($e->getMessage(), 'error');

            return array(
                'result' => 'failed',
            );

        }
    }

    public function check_webhook_stripe_ipn(){
        // j
    }
}




