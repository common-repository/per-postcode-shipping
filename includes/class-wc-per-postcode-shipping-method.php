<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Per_Postcode_Shipping_Method' ) ) {

	/**
	 * Class WC_Per_Postcode_Shipping_Method
	 */
	class WC_Per_Postcode_Shipping_Method extends WC_Shipping_Method {

		/**
		 * WC_Per_Postcode_Shipping_Method constructor.
		 */
		public function __construct() {
			// Shipping method info
			$this->id                 = 'wc_per_postcode_shipping';
			$this->method_title       = __( 'Per Postcode Shipping', 'wc_pps' );
			$this->method_description = __( 'Allows you to set a flat shipping rate per postcode on WooCommerce.', 'wc_pps' );

			// Save settings
			add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );

			// Options and settings
			$this->init_form_fields();
			$this->init_settings();

			// Settings
			$this->title              = $this->settings['title'];
			$this->enabled            = $this->settings['enabled'];
			$this->free_shipping      = $this->settings['free_shipping'];
			$this->per_postcode_rules = $this->settings['per_postcode_rules'];
		}

		/**
		 * Iniciate the form fields
		 */
		public function init_form_fields() {
			$this->form_fields = array(
				'enabled'            => array(
					'title'   => __( 'Enable/Disable', 'wc_pps' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable this shipping method', 'wc_pps' ),
					'default' => 'no',
				),
				'title'              => array(
					'title'       => __( 'Method Title', 'wc_pps' ),
					'type'        => 'text',
					'description' => __( 'This controls the title which the user sees during checkout.', 'wc_pps' ),
					'default'     => __( 'Postcode Shipping', 'wc_pps' ),
				),
				'per_postcode'       => array(
					'title' => __( 'Per Postcode Settings', 'wc_pps' ),
					'type'  => 'title',
				),
				'per_postcode_rules' => array(
					'title'       => __( 'Rules', 'wc_pps' ),
					'type'        => 'textarea',
					'description' => __( 'One rule per line. <br>E.g. 000000-111111|222222-333333:50. <br>Separate postcodes rules by | and the price by : <br>The range separeted by - is the first included and second excluded.', 'wc_pps' ),
				),
				'free_shipping'      => array(
					'title'       => __( 'Free shipping', 'wc_pps' ),
					'type'        => 'text',
					'description' => __( 'Allow free shipping when value is more than this setting. Set it empty for no free shipping.', 'wc_pps' ),
				),
			);
		}

		/**
		 * Admin options output
		 */
		public function admin_options() {
			include 'views/admin-options.php';
		}

		/**
		 * Sanitize postcode for only allow numbers
		 *
		 * @param $postcode The postcode for sanitize
		 *
		 * @return string Postcode sanitized
		 */
		private function sanitize_postcode( $postcode ) {
			return preg_replace( '([^0-9])', '', sanitize_text_field( $postcode ) );
		}

		/**
		 * Check if it's allowed postcode and return the price
		 *
		 * @param $postcode The postcode to check
		 *
		 * @return bool|float|int The price or false if is not allowed
		 */
		private function is_allowed_postcode( $postcode ) {
			// Sanitize postcode
			$postcode = $this->sanitize_postcode( $postcode );
			// Get postcode rules
			$rules = $this->get_postcode_rules();
			// Loop into each postcode rule
			foreach ( $rules as $rule ) {
				// If it's a single postcode rule
				if ( false == $rule['multi'] && $postcode == $rule['start'] ) {
					return $rule['price'];
				}
				// If it's a multiple postcode rule
				if ( true == $rule['multi'] && $postcode >= $rule['start'] && $postcode < $rule['end'] ) {
					return $rule['price'];
				}
			}

			return false;
		}

		/**
		 * Get all the postcode rules
		 * @return array Postcodes ranges
		 */
		private function get_postcode_rules() {
			// Break each line into array
			$rules = preg_split( '/\r\n|[\r\n]/', $this->per_postcode_rules );
			// Empty range set
			$ranges = array();
			// Loop into each line
			foreach ( $rules as $rule ) {
				// Check if it's a empty line
				if ( empty( $rule ) ) {
					continue;
				}
				// Explode price
				$rule = explode( ':', $rule );
				// Store the $rule size
				$rule_size = count( $rule );
				// Store the price exploded before
				$price = $rule_size > 1 ? (float) $rule[1] : 0;
				// Loop into each postcode range
				foreach ( explode( '|', $rule[0] ) as $range ) {
					// Check if it's range
					$rg = explode( '-', $range );
					if ( count( $rg ) > 1 ) {
						// In case it's a range
						$ranges[] = array(
							'start' => trim( $rg[0] ),
							'end'   => trim( $rg[1] ),
							'multi' => true,
							'price' => $price,
						);
					} else {
						// if isn't range
						$ranges[] = array(
							'start' => trim( $rg[0] ),
							'multi' => false,
							'price' => $price,
						);
					}
				}
			}

			return $ranges;
		}

		/**
		 * Calculate the shipping
		 *
		 * @param $package Package shipping
		 */
		public function calculate_shipping( $package ) {
			$postcode   = $package['destination']['postcode'];
			$cart_total = WC()->cart->subtotal;

			$price         = $this->is_allowed_postcode( $postcode );
			$free_shipping = ( ! empty( $this->free_shipping ) && $cart_total >= $this->free_shipping ) ? true : false;
			if ( false !== $price ) {
				$rate = array(
					'id'       => $this->id,
					'label'    => $this->title,
					'cost'     => $free_shipping ? '0.00' : $price,
					'calc_tax' => 'per_item'
				);
				$this->add_rate( $rate );
			}
		}

	}

}