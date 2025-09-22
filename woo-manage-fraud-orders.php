<?php
/**
 * Plugin Name:  WC Anti Fraud
 * Plugin URI:   https://matejzlatic.com
 * Description:  Advanced fraud detection and order management for WooCommerce.
 * Version:           2.9.1
 * Author:       Matej Zlatic
 * Author URI:   https://matejzlatic.com
 * License:      GPLv2
 * License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:  wc-anti-fraud
 * Requires PHP: 7.4
 * WC requires at least: 8.2
 * WC tested up to: 10.1
 * WooCommerce - High-Performance Order Storage (HPOS) compatibility: yes
 *
 * @package woo-manage-fraud-orders
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Check PHP version
if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
	add_action( 'admin_notices', function() {
		echo '<div class="error"><p>' . esc_html__( 'Woo Manage Fraud Orders requires PHP 7.4 or higher.', 'woo-manage-fraud-orders' ) . '</p></div>';
	} );
	return;
}

// Declare HPOS compatibility with version check
add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) &&
	     defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '8.2', '>=' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	} else {
		// HPOS compatibility not declared - WooCommerce version too old or FeaturesUtil not available
	}
} );

if ( ! defined( 'WCAF_PLUGIN_FILE' ) ) {
	define( 'WCAF_PLUGIN_FILE', __FILE__ );
}

if ( ! class_exists( 'WC_Anti_Fraud' ) ) {
	require_once dirname( __FILE__ ) . '/includes/controllers/class-wc-anti-fraud.php';
}

// Include migration handler for backward compatibility
require_once dirname( __FILE__ ) . '/includes/models/class-wcaf-migration-handler.php';

// Initialize the plugin after WooCommerce is loaded
add_action( 'woocommerce_loaded', function() {
	$instance = WC_Anti_Fraud::instance();
	if ( $instance ) {
		$instance->initialize_fraud_tracking();
	}
} );

// Fallback initialization if WooCommerce is already loaded
if ( did_action( 'woocommerce_loaded' ) ) {
	static $fallback_initialized = false;
	if ( ! $fallback_initialized ) {
		$instance = WC_Anti_Fraud::instance();
		if ( $instance ) {
			$instance->initialize_fraud_tracking();
		}
		$fallback_initialized = true;
	}
} elseif ( ! function_exists( 'is_plugin_active' ) ) {
	// If we're in activation context, initialize immediately
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
		static $activation_initialized = false;
		if ( ! $activation_initialized ) {
			$instance = WC_Anti_Fraud::instance();
			if ( $instance ) {
				$instance->initialize_fraud_tracking();
			}
			$activation_initialized = true;
		}
	}
}

// Immediate initialization as additional fallback
add_action( 'init', function() {
	static $initialized = false;
	if ( ! $initialized && class_exists( 'WooCommerce' ) && defined( 'WC_VERSION' ) ) {
		$instance = WC_Anti_Fraud::instance();
		if ( $instance && did_action( 'woocommerce_loaded' ) ) {
			$instance->initialize_fraud_tracking();
		}
		$initialized = true;
	}
}, 5 );
