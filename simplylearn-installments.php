<?php
/**
 * Plugin Name: SimplyLearn Installments
 * Plugin URI:  https://simplylearn.com/
 * Description: Classic Checkout installments gateway with 6/12/24/36 plans, APR + monthly fee, min-total gating, and order meta storage. Optional forward to external provider. Includes Wattn customer verification.
 * Version: 1.3.1
 * Author: SimplyLearn AS
 * Author URI: https://simplylearn.com/
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License: GPLv2 or later
 * Text Domain: sl-installments
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'SLI_VERSION', '1.3.1' );
define( 'SLI_PLUGIN_FILE', __FILE__ );
define( 'SLI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SLI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Load functions
require_once SLI_PLUGIN_DIR . 'includes/functions.php';

// Load customer verification class
require_once SLI_PLUGIN_DIR . 'includes/class-wattn-customer-verification.php';

// Load user profile fields
require_once SLI_PLUGIN_DIR . 'includes/class-wattn-user-profile.php';

// Initialize customer verification
add_action( 'init', function() {
    if ( class_exists( 'Wattn_Customer_Verification' ) ) {
        Wattn_Customer_Verification::init();
    }
    if ( class_exists( 'Wattn_User_Profile' ) ) {
        Wattn_User_Profile::init();
    }
} );

// Add admin notices for verification results
add_action( 'admin_notices', function() {
    if ( ! isset( $_GET['wattn_verified'] ) ) {
        return;
    }
    
    $success = $_GET['wattn_verified'] === '1';
    $user_id = isset( $_GET['user_id'] ) ? intval( $_GET['user_id'] ) : 0;
    
    if ( $success ) {
        $is_customer = get_user_meta( $user_id, 'is_wattn_customer', true );
        $status_text = $is_customer ? 'IS an active Wattn customer' : 'is NOT an active Wattn customer';
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p><strong>Verification Complete:</strong> This user ' . esc_html( $status_text ) . '.</p>';
        echo '</div>';
    } else {
        echo '<div class="notice notice-error is-dismissible">';
        echo '<p><strong>Verification Failed:</strong> Missing required user data (first name, last name, or birth date).</p>';
        echo '</div>';
    }
} );

// Register gateway - ensure WooCommerce is loaded
add_action( 'plugins_loaded', function() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-error"><p>SimplyLearn Installments: WooCommerce not found</p></div>';
        } );
        return;
    }
    
    if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-error"><p>SimplyLearn Installments: WC_Payment_Gateway not found</p></div>';
        } );
        return;
    }
    
    // Load the gateway class now that WooCommerce is available
    require_once SLI_PLUGIN_DIR . 'includes/class-sl-gateway-installments.php';
    
    if ( ! class_exists( 'SLI_Gateway_Installments' ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-error"><p>SimplyLearn Installments: SLI_Gateway_Installments class not found</p></div>';
        } );
        return;
    }
    
    add_filter( 'woocommerce_payment_gateways', function( $methods ) {
        $methods[] = 'SLI_Gateway_Installments';
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-success"><p>SimplyLearn Installments: Gateway registered successfully</p></div>';
        } );
        return $methods;
    } );
} );

// Enqueue frontend styles (classic checkout)
add_action( 'wp_enqueue_scripts', function() {
    if ( function_exists('is_checkout') && is_checkout() ) {
        wp_enqueue_style( 'sli-frontend', SLI_PLUGIN_URL . 'assets/css/frontend.css', [], SLI_VERSION );
        wp_enqueue_script( 'sli-frontend', SLI_PLUGIN_URL . 'assets/js/frontend.js', ['jquery'], SLI_VERSION, true );
    }
} );

// Enqueue admin styles for order screen
add_action( 'admin_enqueue_scripts', function( $hook ) {
    if ( 'post.php' === $hook || 'post-new.php' === $hook ) {
        wp_enqueue_style( 'sli-admin', SLI_PLUGIN_URL . 'assets/css/admin.css', [], SLI_VERSION );
    }
} );

