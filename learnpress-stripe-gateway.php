<?php
/*
Plugin Name: LearnPress Stripe Checkout
Plugin URI: https://amandoabreu.com/wrote/learnpress-stripe-checkout-payment-gateway
Description: Integrates Stripe Checkout with LearnPress for credit card checkout
Version: 5.0
Author: Amando Abreu
Author URI: https://amandoabreu.com/
Require_LP_Version: 4.0.0
*/

// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

const LP_ADDON_STRIPE_GATEWAY_FILE = __FILE__;
const LP_ADDON_STRIPE_GATEWAY_VER = '5.0';
const LP_ADDON_STRIPE_GATEWAY_REQUIRE_VER = '1.0.1';

Class LearnPressStripeGatewayPreload
{
    /**
     * @var array|string[]
     */
    public static $addon_info = array();

    /**
     * LearnPressStripeGatewayPreload constructor.
     */
    public function __construct() {
        load_plugin_textdomain( 'learnpress-stripe', false, basename( dirname(__FILE__) ) . '/languages' );
        add_action( 'learn-press/ready', array( $this, 'load' ) );
        add_action( 'admin_notices', array( $this, 'admin_notices' ) );

        define( 'LP_ADDON_STRIPE_GATEWAY_BASENAME', plugin_basename( LP_ADDON_STRIPE_GATEWAY_FILE ) );

        // Set version addon for LP check .
        include_once ABSPATH . 'wp-admin/includes/plugin.php';
        self::$addon_info = get_file_data(
            LP_ADDON_STRIPE_GATEWAY_FILE,
            array(
                'Name'               => 'Plugin Name',
                'Require_LP_Version' => 'Require_LP_Version',
                'Version'            => 'Version',
            )
        );
    }

    /**
     * Load addon
     */
    public function load() {
        LP_Addon::load( 'LearnPressStripeGateway', 'inc/load.php', __FILE__ );
        remove_action( 'admin_notices', array( $this, 'admin_notices' ) );
    }

    /**
     * Admin notice
     */
    public function admin_notices() {
        ?>
        <div class="error">
            <p><?php echo wp_kses(
                    sprintf(
                        __( '<strong>%s</strong> addon version %s requires %s version %s or higher is <strong>installed</strong> and <strong>activated</strong>.', 'learnpress-idpay' ),
                        __( 'LearnPress Stripe Checkout Gateway', 'learnpress-stripe' ),
                        LP_ADDON_STRIPE_GATEWAY_VER,
                        sprintf( '<a href="%s" target="_blank"><strong>%s</strong></a>', admin_url( 'plugin-install.php?tab=search&type=term&s=learnpress' ), __( 'LearnPress', 'learnpress-idpay' ) ),
                        LP_ADDON_STRIPE_GATEWAY_REQUIRE_VER
                    ),
                    array(
                        'a'      => array(
                            'href'  => array(),
                            'blank' => array()
                        ),
                        'strong' => array()
                    )
                ); ?>
            </p>
        </div>
        <?php
    }

}

new LearnPressStripeGatewayPreload();