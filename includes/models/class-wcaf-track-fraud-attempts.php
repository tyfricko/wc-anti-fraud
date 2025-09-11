<?php
/**
 * Class to track the behavior of customer and block the customer from future
 * checkout process
 *
 * @package wc-anti-fraud
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

if ( ! class_exists( 'WCAF_Track_Fraud_Attempts' ) ) {

	/**
	 * Class WCAF_Track_Fraud_Attempts
	 */
	class WCAF_Track_Fraud_Attempts {

		/**
		 * The singleton instance.
		 *
		 * @var ?WCAF_Track_Fraud_Attempts $instance
		 */
		protected static $instance = null;

		/**
		 * WCAF_Track_Fraud_Attempts constructor.
		 */
		protected function __construct() {
			// Register checkout hooks for both classic and blocks checkout
			// Classic checkout hooks
			add_action( 'woocommerce_checkout_process', array( __CLASS__, 'manage_blacklisted_customers_checkout' ), 0 );
			add_action( 'woocommerce_after_checkout_validation', array( __CLASS__, 'manage_blacklisted_customers_checkout_validation' ), 0, 2 );

			// Blocks checkout / Store API hooks (WooCommerce 10+)
			add_action( 'woocommerce_store_api_checkout_order_processed', array( __CLASS__, 'manage_blacklisted_customers_store_api' ), 0, 1 );
			add_action( 'woocommerce_blocks_checkout_order_processed', array( __CLASS__, 'manage_blacklisted_customers_blocks' ), 0, 1 );

			// Additional checkout hooks
			if ( class_exists( 'Automattic\WooCommerce\Blocks\Package' ) ) {
				add_action( 'woocommerce_store_api_checkout_process_payment', array( __CLASS__, 'manage_blacklisted_customers_blocks_checkout' ), 10, 2 );
			}

			add_action( 'woocommerce_before_pay_action', array( __CLASS__, 'manage_blacklisted_customers_order_pay' ), 99, 1 );
			add_action( 'woocommerce_after_pay_action', array( __CLASS__, 'manage_multiple_failed_attempts_order_pay' ), 99, 1 );
			add_action( 'woocommerce_api_wc_gateway_eway_payment_failed', array( __CLASS__, 'manage_multiple_failed_attempts_eway' ), 100, 4 );
			add_action( 'woocommerce_checkout_order_processed', array( __CLASS__, 'manage_multiple_failed_attempts_checkout' ), 100, 3 );
			add_action( 'woocommerce_order_status_failed', array( __CLASS__, 'manage_multiple_failed_attempts_default' ), 100, 2 );
		}

		/**
		 * Get the class singleton object.
		 *
		 * @return WCAF_Track_Fraud_Attempts
		 */
		public static function instance(): self {
			if ( self::$instance === null ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Log message if debug logging is enabled
		 *
		 * @param string $message The message to log.
		 * @param string $level The log level (debug, error, info).
		 */
		private static function log( $message, $level = 'debug' ) {
			if ( get_option( 'wcaf_enable_debug_log', 'no' ) === 'yes' || $level === 'error' ) {
				$prefix = $level === 'error' ? 'WCAF Error: ' : 'WCAF Debug: ';
				error_log( $prefix . $message );
			}
		}

		/**
		 *
		 * @hooked woocommerce_checkout_process
		 *
		 * @throws Exception
		 * @see WC_Checkout::get_posted_data()
		 *
		 * @see WC_Checkout::validate_checkout()
		 *
		 */
		public static function manage_blacklisted_customers_checkout() {
			// Get posted data first
			$data = WC()->checkout->get_posted_data();


			// This is checked for the woocommerce subscription.
			// If allowed to skip the blacklisting for subscription renewal order payment, return.
			if ( function_exists( 'wcs_cart_contains_renewal' ) ) {
				$cart_item = wcs_cart_contains_renewal();

				if ( isset( $cart_item['subscription_renewal']['renewal_order_id'] ) ) {
					$renewal_order = wc_get_order( $cart_item['subscription_renewal']['renewal_order_id'] );

					if ( $renewal_order ) {
						if ( $renewal_order->get_meta( 'wcaf_skip_blacklist' ) === 'yes' ) {
							return;
						}
					}
				}
			}

			$customer_details = array();

			$first_name                    = $data['billing_first_name'] ?? '';
			$last_name                     = $data['billing_last_name'] ?? '';
			$customer_details['full_name'] = $first_name . ' ' . $last_name;

			$customer_details['billing_email']  = $data['billing_email'] ?? '';
			$customer_details['billing_phone']  = $data['billing_phone'] ?? '';
			$customer_details['payment_method'] = $data['payment_method'] ?? '';

			//customer billing address in single array
			$customer_details['billing_address'] = array();
			if ( isset( $data['billing_address_1'] ) ) {
				$customer_details['billing_address'][] = $data['billing_address_1'];
			}
			if ( isset( $data['billing_address_2'] ) ) {
				$customer_details['billing_address'][] = $data['billing_address_2'];
			}
			if ( isset( $data['billing_city'] ) ) {
				$customer_details['billing_address'][] = $data['billing_city'];
			}
			if ( isset( $data['billing_state'] ) ) {
				$customer_details['billing_address'][] = $data['billing_state'];
			}
			if ( isset( $data['billing_postcode'] ) ) {
				$customer_details['billing_address'][] = $data['billing_postcode'];
			}
			if ( isset( $data['billing_country'] ) ) {
				$customer_details['billing_address'][] = $data['billing_country'];
			}

			//customer shipping address in single array
			$customer_details['shipping_address'] = array();
			if ( isset( $data['shipping_address_1'] ) ) {
				$customer_details['shipping_address'][] = $data['shipping_address_1'];
			}
			if ( isset( $data['shipping_address_2'] ) ) {
				$customer_details['shipping_address'][] = $data['shipping_address_2'];
			}
			if ( isset( $data['shipping_city'] ) ) {
				$customer_details['shipping_address'][] = $data['shipping_city'];
			}
			if ( isset( $data['shipping_state'] ) ) {
				$customer_details['shipping_address'][] = $data['shipping_state'];
			}
			if ( isset( $data['shipping_postcode'] ) ) {
				$customer_details['shipping_address'][] = $data['shipping_postcode'];
			}
			if ( isset( $data['shipping_country'] ) ) {
				$customer_details['shipping_address'][] = $data['shipping_country'];
			}

			if ( count( $customer_details['shipping_address'] ) < 1 ) {
				unset( $customer_details['shipping_address'] );
			}

			$cart_items = WC()->cart->get_cart();

			$product_items = array();
			foreach ( $cart_items as $product_item ) {
				$product_items[] = $product_item['product_id'];
			}

			self::manage_blacklisted_customers( $customer_details, $product_items );
		}

		/**
		 * Handle blacklist checking for classic checkout validation
		 *
		 * @param array<string, mixed> $data Posted data.
		 * @param WP_Error $errors Error object.
		 */
		public static function manage_blacklisted_customers_checkout_validation( $data, $errors ) {
			self::log( 'manage_blacklisted_customers_checkout_validation called' );
			// Check if there are any other errors first.
			// If there are, return.
			if ( ! empty( $errors->errors ) ) {
				return;
			}

			$customer_details = array();

			$first_name                    = $data['billing_first_name'] ?? '';
			$last_name                     = $data['billing_last_name'] ?? '';
			$customer_details['full_name'] = $first_name . ' ' . $last_name;

			$customer_details['billing_email']  = $data['billing_email'] ?? '';
			$customer_details['billing_phone']  = $data['billing_phone'] ?? '';
			$customer_details['payment_method'] = $data['payment_method'] ?? '';

			// Add IP address capture
			$customer_details['ip_address'] = wcaf_get_ip_address();

			//customer billing address in single array
			$customer_details['billing_address'] = array();
			if ( isset( $data['billing_address_1'] ) ) {
				$customer_details['billing_address'][] = $data['billing_address_1'];
			}
			if ( isset( $data['billing_address_2'] ) ) {
				$customer_details['billing_address'][] = $data['billing_address_2'];
			}
			if ( isset( $data['billing_city'] ) ) {
				$customer_details['billing_address'][] = $data['billing_city'];
			}
			if ( isset( $data['billing_state'] ) ) {
				$customer_details['billing_address'][] = $data['billing_state'];
			}
			if ( isset( $data['billing_postcode'] ) ) {
				$customer_details['billing_address'][] = $data['billing_postcode'];
			}
			if ( isset( $data['billing_country'] ) ) {
				$customer_details['billing_address'][] = $data['billing_country'];
			}

			//customer shipping address in single array
			$customer_details['shipping_address'] = array();
			if ( isset( $data['shipping_address_1'] ) ) {
				$customer_details['shipping_address'][] = $data['shipping_address_1'];
			}
			if ( isset( $data['shipping_address_2'] ) ) {
				$customer_details['shipping_address'][] = $data['shipping_address_2'];
			}
			if ( isset( $data['shipping_city'] ) ) {
				$customer_details['shipping_address'][] = $data['shipping_city'];
			}
			if ( isset( $data['shipping_state'] ) ) {
				$customer_details['shipping_address'][] = $data['shipping_state'];
			}
			if ( isset( $data['shipping_postcode'] ) ) {
				$customer_details['shipping_address'][] = $data['shipping_postcode'];
			}
			if ( isset( $data['shipping_country'] ) ) {
				$customer_details['shipping_address'][] = $data['shipping_country'];
			}

			if ( count( $customer_details['shipping_address'] ) < 1 ) {
				unset( $customer_details['shipping_address'] );
			}

			$cart_items = WC()->cart->get_cart();

			$product_items = array();
			foreach ( $cart_items as $product_item ) {
				$product_items[] = $product_item['product_id'];
			}
			self::manage_blacklisted_customers( $customer_details, $product_items );
		}

		/**
		 *
		 * @hooked woocommerce_checkout_order_processed
		 *
		 * @param int $_order_id The order id.
		 * @param array<string, mixed> $_posted_data The checkout data.
		 * @param WC_Order $order The WooCommerce order.
		 *
		 * @throws Exception
		 * @see WC_Checkout::process_checkout()
		 *
		 * @see WC_Checkout::get_posted_data()
		 *
		 */
		public static function manage_multiple_failed_attempts_checkout( $_order_id, $_posted_data, $order ) {
			self::manage_multiple_failed_attempts( $order );
		}

		/**
		 *
		 * @hooked woocommerce_after_pay_action
		 *
		 * @param WC_Order $order The WooCommerce order object.
		 *
		 * @throws Exception
		 * @see WC_Form_Handler::pay_action()
		 *
		 */
		public static function manage_multiple_failed_attempts_order_pay( $order ) {
			self::manage_multiple_failed_attempts( $order, 'order-pay' );
		}

		/**
		 * Triggered when a payment with the gateway fails.
		 *
		 * @param WC_Order $order The order whose payment failed.
		 * @param stdClass $_result The result from the API call.
		 * @param string $_error The error message.
		 * @param WC_Gateway_EWAY $_gateway The instance of the gateway.
		 *
		 * @throws Exception
		 */
		public static function manage_multiple_failed_attempts_eway( $order, $_result, $_error, $_gateway ) {
			self::manage_multiple_failed_attempts( $order, 'order-pay-eway' );
		}

		/**
		 * @param $order_id
		 * @param $order
		 *
		 * @throws Exception
		 */
		public static function manage_multiple_failed_attempts_default( $order_id, $order ) {
			if ( is_admin() ) {
				return;
			}
			self::manage_multiple_failed_attempts( $order, 'failed' );
		}

		/**
		 *
		 * 'manage_multiple_failed_attempts' will only track the multiple failed attempts after the creating of failed
		 * order by customer, This is helpful when customer enter the correct format of the data but payment gateway
		 * couldn't authorize the payment. Typical example will be Electronic check, CC processing.
		 *
		 * @param WC_Order $order The WooCommerce order object.
		 * @param string $context "front"|"order-pay"|"order-pay-eway".
		 *
		 * @throws Exception
		 */
		protected static function manage_multiple_failed_attempts( $order, string $context = 'front' ) {
			try {
				// Validate order object
				if ( ! ( $order instanceof WC_Order ) ) {
					self::log( 'Invalid order object passed to manage_multiple_failed_attempts', 'error' );
					return;
				}

				// As very first step, check if there is product type blacklist.
				// If there are values set to this, we should handle the blacklisting only if customer has such products in cart.
				$product_items = array();
				if ( $order->get_items() && ! empty( $order->get_items() ) ) {
					foreach ( $order->get_items() as $product_item ) {
						$product_item_data = $product_item->get_data();
						if ( isset( $product_item_data['product_id'] ) ) {
							$product_items[] = $product_item_data['product_id'];
						}
					}
				}
			} catch ( Exception $e ) {
				self::log( 'Error in manage_multiple_failed_attempts initialization: ' . $e->getMessage(), 'error' );
				return;
			}

			// If the product type blacklist is configured but none of the order's products are relevant, return.
			$blacklist_product_types = get_option( 'wcaf_black_list_product_types', array() );
			if ( ! empty( $blacklist_product_types ) && ! self::check_products_in_product_type_blacklist( $product_items ) ) {
				return false;
			}

			if ( $order->get_status() === 'failed' || 'failed' === $context ) {
				// Get the allowed failed order limit, default to 5.
				$fraud_limit = get_option( 'wcaf_black_list_allowed_fraud_attempts', 5 );

				// Get customer details
				$customer                             = wcaf_get_customer_details_of_order( $order );
				$fraudulent_details                   = $customer;
				$fraudulent_details['payment_method'] = $order->get_payment_method();

				// Save to the fraud attempt DB table
				self::save_fraud_attempt_record( $fraudulent_details );

				// Save to the order meta
				try {
					$pre_fraud_attempt = (int) $order->get_meta( '_wcaf_fraud_attempts', true );
					$order->update_meta_data( '_wcaf_fraud_attempts', $pre_fraud_attempt + 1 );
					$order->save();
					self::log( 'Updated fraud attempts meta for order ' . $order->get_id() . ' to ' . ($pre_fraud_attempt + 1) );
				} catch ( Exception $e ) {
					self::log( 'Error updating order meta for fraud attempts: ' . $e->getMessage(), 'error' );
				}

				//SERVER side fraud attempts check
				// Check in the order meta
				$order_meta_fraud_status = $pre_fraud_attempt > (int) $fraud_limit;
				if ( $order_meta_fraud_status ) {
					// Block this customer for future sessions as well.
					// And cancel the order.
					if ( false !== $customer && method_exists( 'WCAF_Blacklist_Handler', 'init' ) ) {
						WCAF_Blacklist_Handler::init( $customer, $order, 'add', $context );
						WCAF_Blacklist_Handler::show_blocked_message();

						return false;

					}
				}

				// check in the DB
				if ( self::is_possible_fraud_attempts( $fraud_limit, $customer ) ) {
					WCAF_Blacklist_Handler::init( $customer, $order, 'add', $context );
					WCAF_Blacklist_Handler::show_blocked_message();
				}

			}
		}

		/**
		 * The product type blacklist enabled blacklisting only when at least one product in the order is of a specified type.
		 *
		 * @param int[] $product_items Product ids contained in an order to check against the product type blacklist.
		 *
		 * @return bool
		 */
		public static function check_products_in_product_type_blacklist( $product_items = array() ) {
			$blacklist_product_types = get_option( 'wcaf_black_list_product_types', array() );

			if ( empty( $blacklist_product_types ) ) {
				return false;
			}

			$blacklisted_product_type_found = false;

			foreach ( $product_items as $item ) {
				$product_obj = wc_get_product( $item );
				if ( ! ( $product_obj instanceof WC_Product ) ) {
					continue;
				}
				if ( in_array( $product_obj->get_type(), $blacklist_product_types, true ) ) {
					$blacklisted_product_type_found = true;
					break;
				}
			}

			return $blacklisted_product_type_found;
		}

		/**
		 * @param $customer_details
		 */
		protected static function save_fraud_attempt_record( $customer_details ) {
			$fraud_log_data = array(
				'full_name'        => sanitize_text_field( $customer_details['full_name'] ?? '' ),
				'billing_phone'    => sanitize_text_field( $customer_details['billing_phone'] ?? '' ),
				'ip'               => sanitize_text_field( $customer_details['ip_address'] ?? '' ),
				'billing_email'    => sanitize_email( $customer_details['billing_email'] ?? '' ),
				'billing_address'  => sanitize_text_field( implode( ',', array_filter( $customer_details['billing_address'] ?? array() ) ) ),
				'shipping_address' => sanitize_text_field( implode( ',', array_filter( $customer_details['shipping_address'] ?? array() ) ) ),
				'payment_method'   => sanitize_text_field( $customer_details['payment_method'] ?? '' ),
				'timestamp'        => current_time( 'mysql' ),
			);

			$logs_handler = new WCAF_Fraud_Attempts_DB_Handler();
			$logs_handler->add_fraud_record( $fraud_log_data );
		}

		/**
		 * Check the previous fraud attempts from the DB
		 *
		 * @param $fraud_limit
		 * @param $customer
		 *
		 * @return bool
		 */
		protected static function is_possible_fraud_attempts( $fraud_limit, $customer ) {
			//Check in the DB table
			global $wpdb;
			$checkout_fields = WC()->checkout->get_checkout_fields();

			$where_query = "";
			$args = [];

			if(isset($customer['ip_address'])){
				$where_query .= "ip = %s";
				$args = [$customer['ip_address']];
			}

			if(isset($checkout_fields['billing'])) {
				if(isset($checkout_fields['billing']['billing_email'])
				&& $checkout_fields['billing']['billing_email']['required']) {
					$or_append = $where_query != "" ? " OR " : "";
					$where_query .= $or_append . "billing_email = %s";
					$args[] = $customer['billing_email'];
		 		}

				if(isset($checkout_fields['billing']['billing_phone'])
				&& $checkout_fields['billing']['billing_phone']['required']) {
					$or_append = $where_query != "" ? " OR " : "";
					$where_query .= $or_append . "billing_phone = %s";
					$args[] = $customer['billing_phone'];
				  }
			}

			if($where_query == ""){
				return false;
			}

			$matching_fraud_attempts = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}wcaf_fraud_attempts WHERE ".$where_query,
					$args
				),
				ARRAY_A );
		
			return count( $matching_fraud_attempts ) > (int) $fraud_limit;
		}

		/**
		 * Handle blacklist checking for WooCommerce Blocks checkout
		 *
		 * @param array<string, mixed> $request Request data from Store API
		 * @param \WP_Error $error Error object to add validation errors to
		 */
		public static function manage_blacklisted_customers_blocks_checkout( $request, $error ) {
			try {
				self::log( 'manage_blacklisted_customers_blocks_checkout called' );

				// Extract customer data from the request
				$customer_details = array();

				// Get billing data
				$billing_data = $request['billing_address'] ?? array();
				$customer_details['billing_email'] = $billing_data['email'] ?? '';
				$customer_details['billing_phone'] = $billing_data['phone'] ?? '';
				$customer_details['full_name'] = ($billing_data['first_name'] ?? '') . ' ' . ($billing_data['last_name'] ?? '');

				// Build billing address array
				$customer_details['billing_address'] = array();
				if ( isset( $billing_data['address_1'] ) ) {
					$customer_details['billing_address'][] = $billing_data['address_1'];
				}
				if ( isset( $billing_data['address_2'] ) ) {
					$customer_details['billing_address'][] = $billing_data['address_2'];
				}
				if ( isset( $billing_data['city'] ) ) {
					$customer_details['billing_address'][] = $billing_data['city'];
				}
				if ( isset( $billing_data['state'] ) ) {
					$customer_details['billing_address'][] = $billing_data['state'];
				}
				if ( isset( $billing_data['postcode'] ) ) {
					$customer_details['billing_address'][] = $billing_data['postcode'];
				}
				if ( isset( $billing_data['country'] ) ) {
					$customer_details['billing_address'][] = $billing_data['country'];
				}

				// Get shipping data
				$shipping_data = $request['shipping_address'] ?? array();
				$customer_details['shipping_address'] = array();
				if ( isset( $shipping_data['address_1'] ) ) {
					$customer_details['shipping_address'][] = $shipping_data['address_1'];
				}
				if ( isset( $shipping_data['address_2'] ) ) {
					$customer_details['shipping_address'][] = $shipping_data['address_2'];
				}
				if ( isset( $shipping_data['city'] ) ) {
					$customer_details['shipping_address'][] = $shipping_data['city'];
				}
				if ( isset( $shipping_data['state'] ) ) {
					$customer_details['shipping_address'][] = $shipping_data['state'];
				}
				if ( isset( $shipping_data['postcode'] ) ) {
					$customer_details['shipping_address'][] = $shipping_data['postcode'];
				}
				if ( isset( $shipping_data['country'] ) ) {
					$customer_details['shipping_address'][] = $shipping_data['country'];
				}

				if ( empty( $customer_details['shipping_address'] ) ) {
					unset( $customer_details['shipping_address'] );
				}

				// Add IP address and payment method
				$customer_details['ip_address'] = wcaf_get_ip_address();
				$customer_details['payment_method'] = $request['payment_method'] ?? '';

				// Get cart items
				$cart_items = WC()->cart->get_cart();
				$product_items = array();
				foreach ( $cart_items as $product_item ) {
					$product_items[] = $product_item['product_id'];
				}

				// Check blacklist
				self::manage_blacklisted_customers( $customer_details, $product_items );

			} catch ( Exception $e ) {
				self::log( 'Blocks checkout blacklist error: ' . $e->getMessage(), 'error' );
				$error->add( 'blacklist_error', $e->getMessage() );
			}
		}

		/**
		 * Handle blacklist checking for order pay page
		 *
		 * @param WC_Order $order The order being paid for
		 */
		public static function manage_blacklisted_customers_order_pay( $order ) {
			try {
				self::log( 'manage_blacklisted_customers_order_pay called for order: ' . $order->get_id() );

				// Get customer details from order
				$customer_details = wcaf_get_customer_details_of_order( $order );
				$customer_details['payment_method'] = $order->get_payment_method();

				// Get product items from order
				$product_items = array();
				if ( $order->get_items() ) {
					foreach ( $order->get_items() as $item ) {
						$product_items[] = $item->get_product_id();
					}
				}

				// Check blacklist
				self::manage_blacklisted_customers( $customer_details, $product_items );

			} catch ( Exception $e ) {
				self::log( 'Order pay blacklist error: ' . $e->getMessage(), 'error' );
				// For order pay, redirect back to payment page with error
				$checkout_url = $order->get_checkout_payment_url( false );
				if ( ! wc_has_notice( $e->getMessage(), 'error' ) ) {
					wc_add_notice( $e->getMessage(), 'error' );
				}
				wp_safe_redirect( $checkout_url );
				exit();
			}
		}

		/**
		 * Core method to check if customer is blacklisted and prevent checkout
		 *
		 * @param array<string, mixed> $customer_details Customer information
		 * @param array<int> $product_items Product IDs in cart
		 * @throws Exception If customer is blacklisted
		 */
		protected static function manage_blacklisted_customers( $customer_details, $product_items = array() ) {
			try {
				self::log( 'Checking blacklist for customer: ' . ($customer_details['billing_email'] ?? 'N/A') );

				// Check if customer is whitelisted first
				if ( WCAF_Blacklist_Handler::is_whitelisted( $customer_details ) ) {
					self::log( 'Customer is whitelisted, skipping blacklist check' );
					return;
				}

				// Check product type blacklist
				if ( ! empty( $product_items ) && ! self::check_products_in_product_type_blacklist( $product_items ) ) {
					self::log( 'Product type blacklist active, but no matching products in cart' );
					return;
				}

				// Check if customer is blacklisted
				if ( WCAF_Blacklist_Handler::is_blacklisted( $customer_details ) ) {
					self::log( 'Customer is blacklisted, preventing checkout' );

					// Log the blocked attempt
					WCAF_Blacklist_Handler::add_to_log( $customer_details );

					// Get the custom error message
					$default_notice = esc_html__( 'Sorry, You are being restricted from placing orders.', 'wc-anti-fraud' );
					$wcaf_black_list_message = get_option( 'wcaf_black_list_message', $default_notice );

					// Add error notice to WooCommerce
					if ( ! wc_has_notice( $wcaf_black_list_message, 'error' ) ) {
						wc_add_notice( $wcaf_black_list_message, 'error' );
					}

					// Throw exception to prevent checkout
					throw new Exception( $wcaf_black_list_message );
				}

				self::log( 'Customer passed blacklist check' );

			} catch ( Exception $e ) {
				self::log( 'Error in manage_blacklisted_customers: ' . $e->getMessage(), 'error' );
				throw $e; // Re-throw to prevent checkout
			}
		}

		/**
			* Handle blacklist checking for Store API checkout (WooCommerce 10+)
			*
			* @param WC_Order $order Order object
			* @throws Exception If customer is blacklisted
			*/
		public static function manage_blacklisted_customers_store_api( $order ) {
			if ( ! $order instanceof WC_Order ) {
				return;
			}

			// Get customer details from order
			$customer_details = wcaf_get_customer_details_of_order( $order );

			// Check if blacklisted
			if ( WCAF_Blacklist_Handler::is_blacklisted( $customer_details ) ) {
				// Cancel the order
				WCAF_Blacklist_Handler::cancel_order( $order, 'add' );

				// Throw exception to prevent checkout completion
				throw new Exception( get_option( 'wcaf_black_list_message', esc_html__( 'Sorry, You are being restricted from placing orders.', 'wc-anti-fraud' ) ) );
			}
		}

		/**
			* Handle blacklist checking for Blocks checkout (WooCommerce 10+)
			*
			* @param WC_Order $order Order object
			* @throws Exception If customer is blacklisted
			*/
		public static function manage_blacklisted_customers_blocks( $order ) {
			if ( ! $order instanceof WC_Order ) {
				return;
			}

			// Get customer details from order
			$customer_details = wcaf_get_customer_details_of_order( $order );

			// Check if blacklisted
			if ( WCAF_Blacklist_Handler::is_blacklisted( $customer_details ) ) {
				// Cancel the order
				WCAF_Blacklist_Handler::cancel_order( $order, 'add' );

				// Throw exception to prevent checkout completion
				throw new Exception( get_option( 'wcaf_black_list_message', esc_html__( 'Sorry, You are being restricted from placing orders.', 'wc-anti-fraud' ) ) );
			}
		}
	}
}

// Remove auto-instantiation - let the main controller handle this
