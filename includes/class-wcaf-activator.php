<?php

/**
 * Fired during plugin activation.
 *
 */
class WCAF_Activator {

	public static function create_db_table() {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		global $wpdb;

		// WCAF logs table
		$wcaf_logs_table = $wpdb->prefix . 'wcaf_logs';
		if ( ! ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $wcaf_logs_table ) ) === $wcaf_logs_table ) ) {
			$charset_collate = $wpdb->get_charset_collate();
			$sql             = "CREATE TABLE $wcaf_logs_table (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				full_name varchar(255) DEFAULT '' NOT NULL,
				phone varchar(255) DEFAULT '' NOT NULL,
				ip varchar(255) DEFAULT '' NOT NULL,
				email varchar(255) DEFAULT '' NOT NULL,
				billing_address varchar(255) DEFAULT '' NOT NULL,
				shipping_address varchar(255) DEFAULT '' NOT NULL,
				blacklisted_reason varchar(255) DEFAULT '' NOT NULL,
				timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				PRIMARY KEY  (id)
			) $charset_collate;";

			dbDelta( $sql );

			flush_rewrite_rules();
		}

		//WCAF fraud attempts table
		$wcaf_fraud_attempts_table = $wpdb->prefix . 'wcaf_fraud_attempts';
		if ( ! ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $wcaf_fraud_attempts_table ) ) === $wcaf_fraud_attempts_table ) ) {
			$charset_collate = $wpdb->get_charset_collate();
			$sql             = "CREATE TABLE $wcaf_fraud_attempts_table (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				full_name varchar(255) DEFAULT '' NOT NULL,
				billing_phone varchar(255) DEFAULT '' NOT NULL,
				ip varchar(255) DEFAULT '' NOT NULL,
				billing_email varchar(255) DEFAULT '' NOT NULL,
				billing_address varchar(255) DEFAULT '' NOT NULL,
				shipping_address varchar(255) DEFAULT '' NOT NULL,
				payment_method varchar(255) DEFAULT '' NOT NULL,
				timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				PRIMARY KEY  (id)
			) $charset_collate;";

			dbDelta( $sql );

			flush_rewrite_rules();
		}

		// Run migrations for backward compatibility
		if ( class_exists( 'WCAF_Migration_Handler' ) ) {
			WCAF_Migration_Handler::run_migrations();
		}

	}

	public static function create_upload_dir() {
		if ( ! is_dir( WCAF_LOG_DIR ) ) {
			mkdir( WCAF_LOG_DIR, 0700 );
		}
	}

}
