<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

class WCAF_Fraud_Attempts_DB_Handler {
	public $table = 'wcaf_fraud_attempts';

	/**
	 * Function to add fraud in table
	 *
	 * @param array $data
	 *
	 */
	public function add_fraud_record( $data = array() ) {

		global $wpdb;

		// Define format for each data field for security
		$format = array(
			'full_name'        => '%s',
			'billing_phone'    => '%s',
			'ip'               => '%s',
			'billing_email'    => '%s',
			'billing_address'  => '%s',
			'shipping_address' => '%s',
			'payment_method'   => '%s',
			'timestamp'        => '%s',
		);

		// Filter format array to match provided data
		$data_format = array_intersect_key( $format, $data );

		$wpdb->insert(
			$wpdb->prefix . $this->table,
			$data,
			array_values( $data_format )
		);

	}

	/**
	 * Delete a fraud record.
	 *
	 * @param int $id ID
	 *
	 */
	public function delete_fraud_record( $id ) {
		global $wpdb;
		$wpdb->delete( "{$wpdb->prefix}{$this->table}", array( 'id' => $id ), array( '%d' ) );
	}
}