<?php

/**
 * Plugin Name: Per Postcode Shipping
 * Description: This plugin allows you to set a flat shipping rate per postcode on WooCommerce.
 * Version: 1.0.1
 * Author: Elias Júnior
 * Text Domain: wc_pps
 * Domain Path: /languages
 **/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Per_Postcode_Shipping' ) ) {

	/**
	 * Class WC_Per_Postcode_Shipping
	 */
	class WC_Per_Postcode_Shipping {

		protected static $instance = null;

		/**
		 * WC_Per_Postcode_Shipping constructor.
		 */
		private function __construct() {
			$this->includes();
			$this->load_textdomain();
			if ( $this->is_woocommerce_active() ) {
				add_filter( 'woocommerce_shipping_methods', array( $this, 'add_shipping_methods' ) );
			}
		}

		/**
		 * Return the plugin instance
		 */
		public static function get_instance() {
			if ( null == self::$instance ) {
				self::$instance = new self;
			}

			return self::$instance;
		}

		/**
		 * Include plugin files
		 */
		private function includes() {
			include_once 'includes/class-wc-per-postcode-shipping-method.php';
		}

		/**
		 * Check if WooCommerce is active
		 * @return bool If WooCommerce is active
		 */
		private function is_woocommerce_active() {
			return in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) );
		}

		/**
		 * Add WC_Per_Postcode_Shipping_Method
		 */
		public function add_shipping_methods( $methods ) {
			$methods[] = 'WC_Per_Postcode_Shipping_Method';

			return $methods;
		}

		/**
		 * Load plugin textdomain
		 */
		private function load_textdomain() {
			load_plugin_textdomain( 'wc_pps', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		}


	}

	// Load the plugin
	add_action( 'plugins_loaded', array( 'WC_Per_Postcode_Shipping', 'get_instance' ) );

}