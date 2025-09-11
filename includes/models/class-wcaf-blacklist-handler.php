<?php
/**
 * Handler class to update the blacklisted settings
 * Show the message in checkout page
 *
 * @package wc-anti-fraud
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

if ( ! class_exists( 'WCAF_Blacklist_Handler' ) ) {

	/**
	 * Class WCAF_Blacklist_Handler
	 */
	class WCAF_Blacklist_Handler {

		/**
		 * Get an array of the saved blacklists with caching.
		 *
		 * @used-by self::init()
		 *
		 * @return array<string,string|array>
		 */
		public static function get_blacklists() {
			static $cached_blacklists = null;
			static $cache_time = 0;

			// Cache for 5 minutes to improve performance
			if ( $cached_blacklists !== null && ( time() - $cache_time ) < 300 ) {
				return $cached_blacklists;
			}

			$cached_blacklists = array(
				'prev_black_list_ips'        => get_option( 'wcaf_black_list_ips', '' ),
				'prev_wcaf_black_list_names' => get_option( 'wcaf_black_list_names', '' ),
				'prev_black_list_phones'     => get_option( 'wcaf_black_list_phones' ),
				'prev_black_list_emails'     => get_option( 'wcaf_black_list_emails', '' ),
				'prev_black_list_addresses'  => get_option( 'wcaf_black_list_addresses', '' ),
			);

			$cache_time = time();
			return $cached_blacklists;
		}

		/**
		 * Clear blacklist cache
		 */
		private static function clear_blacklist_cache() {
			// Clear static cache by resetting static variables
			// This is a simple way to invalidate cache
			if ( function_exists( 'wp_cache_flush' ) ) {
				wp_cache_flush();
			}
		}

		/**
		 * Add or remove a specified entry from the saved values.
		 *
		 * @param string $key The wp_options name.
		 * @param string $pre_values The preexisting values, as a string, one per line.
		 * @param string $to_add The value(s) to add.
		 * @param string $action "add"|"remove".
		 */
		public static function update_blacklist( $key, $pre_values, $to_add, $action = 'add' ) {
			$new_values = null;
			if ( 'wcaf_black_list_addresses' !== $key ) {
				$to_add = str_replace( PHP_EOL, '', $to_add );
			}

			if ( 'add' === $action ) {
				if ( empty( $pre_values ) ) {
					$new_values = $to_add;
				} else {
					$to_add_entries = explode( PHP_EOL, $to_add );

					foreach ( $to_add_entries as $to_add_entry ) {
						$new_values = ! in_array( $to_add_entry, explode( PHP_EOL, $pre_values ), true ) ? $pre_values . PHP_EOL . $to_add_entry : $pre_values;
					}
				}
			} elseif ( 'remove' === $action ) {

				$in_array_value    = explode( PHP_EOL, $pre_values );
				$to_remove_entries = explode( PHP_EOL, $to_add );

				foreach ( $to_remove_entries as $to_remove_entry ) {
					if ( in_array( $to_remove_entry, $in_array_value, true ) ) {
						$array_key = array_search( $to_remove_entry, $in_array_value, true );
						if ( false !== $array_key ) {
							unset( $in_array_value[ $array_key ] );
						}
					}
				}

				$new_values = implode( PHP_EOL, $in_array_value );
			}

			if ( ! is_null( $new_values ) ) {
				update_option( $key, trim( $new_values ) );
				self::clear_blacklist_cache();
			}
		}

		/**
		 *
		 * When $context is front, we are customer facing so throw an exception to display an error to them.
		 *
		 * @param array<string,string|array>|false $customer Customer details (optional if an order is provided).
		 * @param ?WC_Order $order A WooCommerce order (option if customer details are provided).
		 * @param string $action "add"|"remove".
		 * @param string $context "front"|"order-pay-eway".
		 *
		 * @return bool
		 * @throws Exception
		 * @see wcaf_get_customer_details_of_order()
		 *
		 */
		public static function init( $customer = array(), $order = null, $action = 'add', $context = 'front' ) {
			$prev_blacklisted_data = self::get_blacklists();
			if ( empty( $customer ) ) {
				return false;
			}

			$allow_blacklist_by_name         = get_option( 'wcaf_allow_blacklist_by_name', 'no' );
			$wcaf_allow_blacklist_by_address = get_option( 'wcaf_allow_blacklist_by_address', 'yes' );

			if ( 'yes' == $allow_blacklist_by_name ) {
				self::update_blacklist( 'wcaf_black_list_names', $prev_blacklisted_data['prev_wcaf_black_list_names'], $customer['full_name'], $action );

			}
			self::update_blacklist( 'wcaf_black_list_ips', $prev_blacklisted_data['prev_black_list_ips'], $customer['ip_address'], $action );
			self::update_blacklist( 'wcaf_black_list_phones', $prev_blacklisted_data['prev_black_list_phones'], $customer['billing_phone'], $action );
			self::update_blacklist( 'wcaf_black_list_emails', $prev_blacklisted_data['prev_black_list_emails'], $customer['billing_email'], $action );

			if ( 'no' != $wcaf_allow_blacklist_by_address ) {
				// If billing and shipping address are the same, only save one.
				if ( ! isset( $customer['shipping_address'] ) ) {
					$addresses = implode( ',', $customer['billing_address'] );
				} else {
					$addresses = implode( PHP_EOL, array_unique( array(
						implode( ',', $customer['billing_address'] ),
						implode( ',', $customer['shipping_address'] ),
					) ) );
				}


				self::update_blacklist( 'wcaf_black_list_addresses', $prev_blacklisted_data['prev_black_list_addresses'], $addresses, $action );

			}

			if ( in_array( $context, array( 'front', 'failed' ), true ) ) {
				$GLOBALS['first_caught_blacklisted_reason'] = __( 'Max Fraud Attempts exceeded', 'wc-anti-fraud' );
				WCAF_Blacklist_Handler::add_to_log( $customer );
			}

			// Handle the cancellation of order.
			if ( null !== $order ) {
				$default_notice          = esc_html__( 'Sorry, You are being restricted from placing orders.', 'wc-anti-fraud' );
				$wcaf_black_list_message = get_option( 'wcaf_black_list_message', $default_notice );
				self::cancel_order( $order, $action );

				if ( 'front' === $context ) {
					throw new Exception( $wcaf_black_list_message );
				}

				if ( in_array( $context, array( 'order-pay', 'order-pay-eway' ), true ) ) {
					if ( ! wc_has_notice( $wcaf_black_list_message, 'error' ) ) {
						wc_add_notice( $wcaf_black_list_message, 'error' );
					}
				}

				if ( 'order-pay-eway' === $context ) {
					// phpcs:ignore WordPress.Security.NonceVerification.Recommended
					if ( isset( $_GET['AccessCode'] ) ) {
						wp_safe_redirect( $order->get_checkout_payment_url( false ) );
						exit();
					} else {
						throw new Exception();
					}
				}
			}

			return true;
		}

		/**
		 * Sets the order status to cancelled and adds a note saying the details are blacklisted.
		 *
		 * When $action=='remove' it adds a note saying the details are no longer blacklisted.
		 *
		 * @param WC_Order $order The WooCommerce order.
		 * @param string $action "add"|"remove".
		 *
		 * @return bool Always returns true.
		 */
		public static function cancel_order( $order, $action = 'add' ) {
			try {
				if ( ! ( $order instanceof WC_Order ) ) {
					error_log( 'WCAF Error: Invalid order object passed to cancel_order' );
					return false;
				}

				if ( 'remove' === $action ) {
					$order->add_order_note( apply_filters( 'wcaf_remove_blacklisted_order_note', esc_html__( 'Order details removed from blacklist.', 'wc-anti-fraud' ) ) );
					return true;
				}

				$blacklisted_order_note = apply_filters( 'wcaf_blacklisted_order_note', esc_html__( 'Order details blacklisted for future checkout.', 'wc-anti-fraud' ), $order );

				// Set the order status to "Cancelled".
				if ( ! $order->has_status( 'cancelled' ) && $order->get_type() === 'shop_order' ) {
					$order->update_status( 'cancelled', $blacklisted_order_note );
				}

				$order->add_order_note( $blacklisted_order_note );
				$order->update_meta_data( '_wcaf_cancelled', 'yes' );
				$order->save();

				error_log( 'WCAF: Order ' . $order->get_id() . ' cancelled due to blacklisting' );
				return true;
			} catch ( Exception $e ) {
				error_log( 'WCAF Error in cancel_order: ' . $e->getMessage() );
				return false;
			}
		}

		/**
		 * Show the blocked message to the customer.
		 */
		public static function show_blocked_message() {
			$default_notice          = esc_html__( 'Sorry, You are being restricted from placing orders.', 'wc-anti-fraud' );
			$wcaf_black_list_message = wcaf_get_option_with_fallback( 'wcaf_black_list_message', $default_notice, 'wmfo_black_list_message' );

			// with some reason, get_option with default value not working.

			if ( function_exists( 'wc_has_notice' ) && ! wc_has_notice( $wcaf_black_list_message ) ) {
				wc_add_notice( $wcaf_black_list_message, 'error' );
			}
		}

		/**
		 * @param $customer_details
		 */
		public static function add_to_log( $customer_details ) {
			global $first_caught_blacklisted_reason;
			// Add log to file
			$wcaf_enable_debug_log = get_option( 'wcaf_enable_debug_log', 'no' );

			if ( $wcaf_enable_debug_log === 'yes' ) {
				$debug_log = new WCAF_Debug_Log();
				$debug_log->write( '----------start------------' );
				$debug_log->write( 'Customer Details ==>' );
				$debug_log->write( $customer_details );

				$debug_log->write( 'Block type ==> ' . $first_caught_blacklisted_reason );
				$debug_log->write( 'Timestamp ==> ' . current_time( 'mysql' ) );

				$debug_log->write( '----------end------------' );
				$debug_log->write();
				$debug_log->write();
				$debug_log->save();
			}

			//Add log to DB table
			$wcaf_enable_db_log = get_option( 'wcaf_enable_db_log', 'yes' );

			if ( 'no' !== $wcaf_enable_db_log ) {
				$log_data = array(
					'full_name'          => $customer_details['full_name'] ?? 'N/A',
					'phone'              => $customer_details['billing_phone'] ?? 'N/A',
					'ip'                 => $customer_details['ip_address'] ?? 'N/A',
					'email'              => $customer_details['billing_email'] ?? 'NA',
					'billing_address'    => isset( $customer_details['billing_address'] ) ? implode( ',', $customer_details['billing_address'] ) : '',
					'shipping_address'   => isset( $customer_details['shipping_address'] ) ? implode( ',', $customer_details['shipping_address'] ) : '',
					'blacklisted_reason' => $first_caught_blacklisted_reason ?? 'N/A',
					'timestamp'          => current_time( 'mysql' ),
				);

				$logs_handler = new WCAF_Logs_Handler();
				$logs_handler->add_log( $log_data );
			}

		}

		/**
		 * Check if the current details are whitelisted
		 * Whitelist by payment gateway
		 * Whitelist by user
		 *
		 * @param $customer_details
		 *
		 * @return bool
		 */
		 public static function is_whitelisted( $customer_details ) {
		 		$wcaf_white_listed_payment_gateways = get_option( 'wcaf_white_listed_payment_gateways', array() );
		 		$wcaf_white_listed_customers        = get_option( 'wcaf_white_listed_customers', "" );

	 			$current_user = wp_get_current_user();

	 			if ( in_array( $customer_details['payment_method'], $wcaf_white_listed_payment_gateways, true ) ) {
	 				return true;
	 			} elseif (
	 				 in_array(
	 					 (string) get_current_user_id(),
	 						array_map( 'strtolower',
	 							array_map(
	 								'trim',
	 								explode( PHP_EOL, $wcaf_white_listed_customers )
	 							)
	 						),
	 				 true
	 				 ) ) {
	 				return true;
	 			}elseif(
	 				$current_user->ID &&
	 				in_array(
	 					$current_user->user_email,
	 					array_map( 'strtolower',
	 						array_map(
	 							'trim',
	 							explode( PHP_EOL, $wcaf_white_listed_customers )
	 						)
	 					),
	 					)
	 			){
	 				return true;
	 			}
	 
	 			if ( get_option( 'wcaf_enable_debug_log', 'no' ) === 'yes' ) {
	 				error_log( 'WCAF Debug: is_blacklisted() returning FALSE - customer not blacklisted' );
	 			}
	 
	 			return false;
	 		}

		/**
		 * The main function in the plugin: checks is the customer details blacklisted against the saved settings.
		 *
		 * @param array<string, string> $customer_details The details to check.
		 *
		 * @return bool
		 * @see wcaf_get_customer_details_of_order()
		 *
		 */
		public static function is_blacklisted( $customer_details ) {
			if ( get_option( 'wcaf_enable_debug_log', 'no' ) === 'yes' ) {
				error_log( 'WCAF Debug: is_blacklisted() called with customer data: ' . json_encode($customer_details) );
				error_log( 'WCAF Debug: Checking if blacklisted for email: ' . ($customer_details['billing_email'] ?? 'N/A') . ', phone: ' . ($customer_details['billing_phone'] ?? 'N/A') );
			}
			// Check for ony by one, return TRUE as soon as first matching.
			$allow_blacklist_by_name         = get_option( 'wcaf_allow_blacklist_by_name', 'no' );
			$wcaf_allow_blacklist_by_email_wildcard         = get_option( 'wcaf_allow_blacklist_by_email_wildcard', 'no' );
			$wcaf_allow_blacklist_by_address = get_option( 'wcaf_allow_blacklist_by_address', 'yes' );
			$blacklisted_customer_names      = get_option( 'wcaf_black_list_names' );
			$blacklisted_ips                 = get_option( 'wcaf_black_list_ips' );
			$blacklisted_emails              = get_option( 'wcaf_black_list_emails' );
			$blacklisted_email_domains       = get_option( 'wcaf_black_list_email_domains' );
			$blacklisted_phones              = get_option( 'wcaf_black_list_phones' );
			$blacklisted_addresses           = get_option( 'wcaf_black_list_addresses' );

			if ( get_option( 'wcaf_enable_debug_log', 'no' ) === 'yes' ) {
				error_log( 'WCAF Debug: Blacklisted emails: ' . $blacklisted_emails );
				error_log( 'WCAF Debug: Blacklisted phones: ' . $blacklisted_phones );
			}

			$email  = $customer_details['billing_email'];
			$domain = substr( $email, strpos( $email, '@' ) + 1 );

			// Check blacklist by names
			if ( 'yes' === $allow_blacklist_by_name &&
			     ! empty( $blacklisted_customer_names ) &&
			     in_array(
				     strtolower( $customer_details['full_name'] ),
				     array_map( 'strtolower',
					     array_map( 'trim',
						     explode( PHP_EOL, $blacklisted_customer_names )
					     )
				     ),
				     true
			     ) ) {
				if ( get_option( 'wcaf_enable_debug_log', 'no' ) === 'yes' ) {
					error_log( 'WCAF Debug: Name blacklisted: ' . $customer_details['full_name'] );
				}
				$GLOBALS['first_caught_blacklisted_reason'] = __( 'Full Name', 'wc-anti-fraud' );

				return true;
			} elseif ( ! empty( $blacklisted_ips ) &&
			           in_array(
				           strtolower( $customer_details['ip_address'] ),
				           array_map( 'strtolower',
					           array_map( 'trim',
						           explode( PHP_EOL, $blacklisted_ips )
					           )
				           ),
				           true
			           ) ) {
				if ( get_option( 'wcaf_enable_debug_log', 'no' ) === 'yes' ) {
					error_log( 'WCAF Debug: IP blacklisted: ' . $customer_details['ip_address'] );
				}
				$GLOBALS['first_caught_blacklisted_reason'] = __( 'IP Address', 'wc-anti-fraud' );

				return true;
			} elseif ( ! empty( $blacklisted_emails ) &&
				          in_array(
				           strtolower( $customer_details['billing_email'] ),
				           array_map( 'strtolower',
					           array_map( 'trim',
						           explode( PHP_EOL, $blacklisted_emails )
					           )
				           ),
				           true
				          ) ) {
				if ( get_option( 'wcaf_enable_debug_log', 'no' ) === 'yes' ) {
					error_log( 'WCAF Debug: Email blacklisted: ' . $customer_details['billing_email'] );
				}
				$GLOBALS['first_caught_blacklisted_reason'] = __( 'Billing Email', 'wc-anti-fraud' );

				return true;

			} elseif ( ! empty( $blacklisted_email_domains ) &&
				          in_array(
				           strtolower( $domain ),
				           array_map( 'strtolower',
					           array_map(
						           'trim',
						           explode( PHP_EOL, $blacklisted_email_domains )
					           )
				           ),
				           true ) ) {
				$GLOBALS['first_caught_blacklisted_reason'] = __( 'Email Domain', 'wc-anti-fraud' );

				return true;
			} elseif ( ! empty( $blacklisted_phones ) &&
				          in_array(
				           str_replace( ' ', '', strtolower( trim( $customer_details['billing_phone'] ) ) ),
				           array_map( function( $phone ) {
					           return str_replace( ' ', '', strtolower( trim( $phone ) ) );
				           }, explode( PHP_EOL, $blacklisted_phones ) ),
				           true ) ) {
				if ( get_option( 'wcaf_enable_debug_log', 'no' ) === 'yes' ) {
					error_log( 'WCAF Debug: Phone blacklisted: ' . $customer_details['billing_phone'] );
				}
				$GLOBALS['first_caught_blacklisted_reason'] = __( 'Billing Phone', 'wc-anti-fraud' );

				return true;
			} elseif ( ! empty( $blacklisted_email_domains ) &&
				          in_array(
				           strtolower( $domain ),
				           array_map( 'strtolower',
					           array_map(
						           'trim',
						           explode( PHP_EOL, $blacklisted_email_domains )
					           )
				           ),
				           true ) ) {
				if ( get_option( 'wcaf_enable_debug_log', 'no' ) === 'yes' ) {
					error_log( 'WCAF Debug: Email domain blacklisted: ' . $domain );
				}
				$GLOBALS['first_caught_blacklisted_reason'] = __( 'Email Domain', 'wc-anti-fraud' );

				return true;
			}
//check for email wildcard
if($wcaf_allow_blacklist_by_email_wildcard === "yes"){
	$is_wildcard_email_caught = false;
	if ( ! empty( $blacklisted_emails ) ) {
		foreach (
			array_map( 'strtolower',
				array_map( 'trim',
					explode( PHP_EOL, $blacklisted_emails )
				)
			) as $email_wild_card
		) {
			if ( strpos( strtolower( $customer_details['billing_email'] ), $email_wild_card ) !== false ) {
				$is_wildcard_email_caught = true;
				break;
			}
		}
	}


	if ( $is_wildcard_email_caught ) {
		$GLOBALS['first_caught_blacklisted_reason'] = __( 'Billing Email Wildcard match', 'wc-anti-fraud' );

		return true;
	}
}

			

			if ( 'no' == $wcaf_allow_blacklist_by_address ) {

				return false;
			}
			// Map country name to country code.
			// AF => Afghanistan.
			$countries_list = WC()->countries->get_countries();
			$countries_list = array_map( 'strtolower', $countries_list );
			$countries_list = array_flip( $countries_list );

			$customer_billing_address_parts = $customer_details['billing_address'] ?? array();
			$customer_billing_address_parts = array_map(
				'strtolower',
				array_map(
					function ( $element ) use ( $countries_list ) {
						if ( isset( $countries_list[ $element ] ) ) {
							return $countries_list[ $element ];
						}

						return trim( $element );
					},
					$customer_billing_address_parts
				)
			);

			$customer_shipping_address_parts = $customer_details['shipping_address'] ?? array();
			$customer_shipping_address_parts = array_map(
				'strtolower',
				array_map(
					function ( $element ) use ( $countries_list ) {
						if ( isset( $countries_list[ $element ] ) ) {
							return $countries_list[ $element ];
						}

						return trim( $element );
					},
					$customer_shipping_address_parts
				)
			);

			foreach ( array_filter( explode( PHP_EOL, strtolower( $blacklisted_addresses ) ) ) as $blacklisted_address ) {
				$blacklisted_address_parts = explode( ',', $blacklisted_address );
				$blacklisted_address_parts = array_map(
					function ( $element ) use ( $countries_list ) {
						if ( isset( $countries_list[ $element ] ) ) {
							return $countries_list[ $element ];
						}

						return trim( $element );
					},
					$blacklisted_address_parts
				);

				/**
				 * Check address by wildcard
				 * It has to be in %address% format
				 */
				if ( count( $blacklisted_address_parts ) === 1 ) {
					if ( substr_compare( $blacklisted_address_parts[0], '%', 0, strlen( '%' ) ) === 0 &&
					     substr_compare( $blacklisted_address_parts[0], '%', - strlen( '%' ) ) === 0
					) {
						$wild_card_val = strtolower( trim( $blacklisted_address_parts[0], '%' ) );
						if ( $wild_card_val != '' ) {

							// check by array
							if ( in_array( $wild_card_val, $customer_billing_address_parts ) ||
							     in_array( $wild_card_val, $customer_shipping_address_parts )
							) {
								$GLOBALS['first_caught_blacklisted_reason'] = __( 'Billing/Shipping Address', 'wc-anti-fraud' );

								return true;
							}

							// check by string
							if ( strpos( implode( ' ', $customer_billing_address_parts ), $wild_card_val ) !== false ||
							     strpos( implode( ' ', $customer_shipping_address_parts ), $wild_card_val ) !== false
							) {
								$GLOBALS['first_caught_blacklisted_reason'] = __( 'Billing/Shipping Address', 'wc-anti-fraud' );

								return true;
							}
						}

					}
				}
				/**
				 * If all the parts of the blacklisted address are in the customer's address
				 *
				 * @see https://stackoverflow.com/a/22651134/
				 */
				$address_match = ! array_diff( $blacklisted_address_parts, $customer_billing_address_parts )
				                 || ! array_diff( $blacklisted_address_parts, $customer_shipping_address_parts );

				if ( $address_match ) {
					$GLOBALS['first_caught_blacklisted_reason'] = __( 'Billing/Shipping Address', 'wc-anti-fraud' );

					return true;
				}
			}

			return false;
		}
	}
}
