<?php
/**
 * Global functions related fraud management
 * Function to update the block list details
 *
 * @package wc-anti-fraud
 */

/**
 * Function to get the customer details
 * Billing Phone, Email and IP address
 *
 * @param WC_Order $order The WooCommerce order object.
 *
 * @return array<string,string|array>|false
 */
function wcaf_get_customer_details_of_order( WC_Order $order ): array|false {
	if ( ! ( $order instanceof WC_Order ) ) {
		error_log( 'WCAF Error: Invalid order object passed to wcaf_get_customer_details_of_order' );
		return false;
	}

	// Additional security check for order ID
	$order_id = $order->get_id();
	if ( ! is_numeric( $order_id ) || $order_id <= 0 ) {
		error_log( 'WCAF Error: Invalid order ID: ' . $order_id );
		return false;
	}

	// Get billing address components
	$billing_address = array(
		'address_1' => $order->get_billing_address_1(),
		'address_2' => $order->get_billing_address_2(),
		'city'      => $order->get_billing_city(),
		'state'     => $order->get_billing_state(),
		'postcode'  => $order->get_billing_postcode(),
		'country'   => $order->get_billing_country(),
	);

	// Get shipping address components
	$shipping_address = array(
		'address_1' => $order->get_shipping_address_1(),
		'address_2' => $order->get_shipping_address_2(),
		'city'      => $order->get_shipping_city(),
		'state'     => $order->get_shipping_state(),
		'postcode'  => $order->get_shipping_postcode(),
		'country'   => $order->get_shipping_country(),
	);

	return array(
		'full_name'        => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
		'ip_address'       => method_exists( 'WC_Geolocation', 'get_ip_address' ) ? WC_Geolocation::get_ip_address() : wcaf_get_ip_address(),
		'billing_phone'    => $order->get_billing_phone(),
		'billing_email'    => $order->get_billing_email(),
		'billing_address'  => array_filter( array_map( 'trim', array_values( $billing_address ) ) ),
		'shipping_address' => array_filter( array_map( 'trim', array_values( $shipping_address ) ) ) ?? array(),
	);
}

/**
 *
 * In case woo commerce changes the function name to get IP address,
 */
function wcaf_get_ip_address(): string {
	if ( isset( $_SERVER['HTTP_X_REAL_IP'] ) ) { // WPCS: input var ok, CSRF ok.
		return sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REAL_IP'] ) ); // WPCS: input var ok, CSRF ok.
	} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) { // WPCS: input var ok, CSRF ok.
		// Proxy servers can send through this header like this: X-Forwarded-For: client1, proxy1, proxy2
		// Make sure we always only send through the first IP in the list which should always be the client IP.
		return (string) rest_is_ip_address( trim( current( preg_split( '/[,:]/', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) ) ) ) ); // WPCS: input var ok, CSRF ok.
	} elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
		return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
	}

	return '';
}

/**
 * @return array<string,string>
 */
function wcafp_get_customers(): array {
	$all_users = get_users();

	$formatted_all_users = array();
	foreach ( $all_users as $key => $user ) {
		$formatted_all_users[ $user->get( 'ID' ) ] = $user->get( 'user_login' );
	}

	return $formatted_all_users;
}


/**
 * get enabled gateways
 * @return array<string,string>
 */
function wcafp_get_enabled_payment_gateways(): array {
	$available_payment_gateways           = WC()->payment_gateways->get_available_payment_gateways();
	$formatted_available_payment_gateways = array();
	foreach ( $available_payment_gateways as $key => $available_payment_gateway ) {
		$method_title = $available_payment_gateway->title;
		if(!$method_title){
			$method_title = $available_payment_gateway->method_title;
		}
		$formatted_available_payment_gateways[ $key ] = $method_title;
	}

	return $formatted_available_payment_gateways;
}

/**
	* Get option with backward compatibility fallback
	*
	* @param string $new_option New option name
	* @param mixed $default Default value
	* @param string $old_option Old option name for fallback
	* @return mixed
	*/
function wcaf_get_option_with_fallback( $new_option, $default = false, $old_option = '' ) {
	$value = get_option( $new_option, false );

	// If new option exists, return it
	if ( $value !== false ) {
		return $value;
	}

	// If old option exists, return it (migration will handle updating later)
	if ( ! empty( $old_option ) ) {
		$old_value = get_option( $old_option, false );
		if ( $old_value !== false ) {
			return $old_value;
		}
	}

	return $default;
}

/**
	* Get post meta with backward compatibility fallback
	*
	* @param int $post_id Post ID
	* @param string $new_key New meta key
	* @param mixed $default Default value
	* @param string $old_key Old meta key for fallback
	* @return mixed
	*/
function wcaf_get_meta_with_fallback( $post_id, $new_key, $default = false, $old_key = '' ) {
	$value = get_post_meta( $post_id, $new_key, true );

	// If new meta exists, return it
	if ( $value !== '' ) {
		return $value;
	}

	// If old meta exists, return it (migration will handle updating later)
	if ( ! empty( $old_key ) ) {
		$old_value = get_post_meta( $post_id, $old_key, true );
		if ( $old_value !== '' ) {
			return $old_value;
		}
	}

	return $default;
}
