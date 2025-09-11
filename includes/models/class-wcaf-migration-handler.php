<?php
/**
 * Handles backward compatibility and data migration for WC Anti Fraud plugin
 *
 * @package wc-anti-fraud
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

class WCAF_Migration_Handler {

	/**
	 * Migration version option key
	 */
	const MIGRATION_VERSION_KEY = 'wcaf_migration_version';

	/**
	 * Current migration version
	 */
	const CURRENT_VERSION = '2.7.0';

	/**
	 * Run all necessary migrations
	 */
	public static function run_migrations() {
		// Only run migrations if WooCommerce is active
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			return;
		}

		$current_version = get_option( self::MIGRATION_VERSION_KEY, '0' );

		if ( version_compare( $current_version, self::CURRENT_VERSION, '<' ) ) {
			self::migrate_database_tables();
			self::migrate_options();
			self::migrate_order_meta();
			self::cleanup_old_data();

			update_option( self::MIGRATION_VERSION_KEY, self::CURRENT_VERSION );
		}
	}

	/**
	 * Migrate data from old database tables to new ones
	 */
	private static function migrate_database_tables() {
		global $wpdb;

		try {
			// Migrate wmfo_logs to wcaf_logs
			$old_logs_table = $wpdb->prefix . 'wmfo_logs';
			$new_logs_table = $wpdb->prefix . 'wcaf_logs';

			if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $old_logs_table ) ) === $old_logs_table ) {
				$old_logs = $wpdb->get_results( "SELECT * FROM {$old_logs_table}", ARRAY_A );

				if ( ! empty( $old_logs ) ) {
					foreach ( $old_logs as $log ) {
						$mapped_log = array(
							'id' => $log['id'],
							'full_name' => sanitize_text_field( $log['full_name'] ?? '' ),
							'phone' => sanitize_text_field( $log['phone'] ?? '' ),
							'ip' => sanitize_text_field( $log['ip'] ?? '' ),
							'email' => sanitize_email( $log['email'] ?? '' ),
							'billing_address' => sanitize_text_field( $log['billing_address'] ?? '' ),
							'shipping_address' => sanitize_text_field( $log['shipping_address'] ?? '' ),
							'blacklisted_reason' => sanitize_text_field( $log['blacklisted_reason'] ?? '' ),
							'timestamp' => sanitize_text_field( $log['timestamp'] ?? current_time( 'mysql' ) ),
						);

						$wpdb->insert( $new_logs_table, $mapped_log );
					}
				}
			}

			// Migrate wmfo_fraud_attempts to wcaf_fraud_attempts
			$old_fraud_table = $wpdb->prefix . 'wmfo_fraud_attempts';
			$new_fraud_table = $wpdb->prefix . 'wcaf_fraud_attempts';

			if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $old_fraud_table ) ) === $old_fraud_table ) {
				$old_fraud_attempts = $wpdb->get_results( "SELECT * FROM {$old_fraud_table}", ARRAY_A );

				if ( ! empty( $old_fraud_attempts ) ) {
					foreach ( $old_fraud_attempts as $attempt ) {
						$mapped_attempt = array(
							'id' => $attempt['id'],
							'full_name' => sanitize_text_field( $attempt['full_name'] ?? '' ),
							'billing_phone' => sanitize_text_field( $attempt['billing_phone'] ?? '' ),
							'ip' => sanitize_text_field( $attempt['ip'] ?? '' ),
							'billing_email' => sanitize_email( $attempt['billing_email'] ?? '' ),
							'billing_address' => sanitize_text_field( $attempt['billing_address'] ?? '' ),
							'shipping_address' => sanitize_text_field( $attempt['shipping_address'] ?? '' ),
							'payment_method' => sanitize_text_field( $attempt['payment_method'] ?? '' ),
							'timestamp' => sanitize_text_field( $attempt['timestamp'] ?? current_time( 'mysql' ) ),
						);

						$wpdb->insert( $new_fraud_table, $mapped_attempt );
					}
				}
			}
		} catch ( Exception $e ) {
			error_log( 'WCAF Migration Error (Database): ' . $e->getMessage() );
		}
	}

	/**
	 * Migrate old options to new option names
	 */
	private static function migrate_options() {
		$option_mappings = array(
			'wmfo_black_list_ips' => 'wcaf_black_list_ips',
			'wmfo_black_list_names' => 'wcaf_black_list_names',
			'wmfo_black_list_phones' => 'wcaf_black_list_phones',
			'wmfo_black_list_emails' => 'wcaf_black_list_emails',
			'wmfo_black_list_email_domains' => 'wcaf_black_list_email_domains',
			'wmfo_black_list_addresses' => 'wcaf_black_list_addresses',
			'wmfo_black_list_message' => 'wcaf_black_list_message',
			'wmfo_allow_blacklist_by_name' => 'wcaf_allow_blacklist_by_name',
			'wmfo_allow_blacklist_by_address' => 'wcaf_allow_blacklist_by_address',
			'wmfo_allow_blacklist_by_email_wildcard' => 'wcaf_allow_blacklist_by_email_wildcard',
			'wmfo_black_list_allowed_fraud_attempts' => 'wcaf_black_list_allowed_fraud_attempts',
			'wmfo_black_list_product_types' => 'wcaf_black_list_product_types',
			'wmfo_black_list_order_status' => 'wcaf_black_list_order_status',
			'wmfo_white_listed_payment_gateways' => 'wcaf_white_listed_payment_gateways',
			'wmfo_white_listed_customers' => 'wcaf_white_listed_customers',
			'wmfo_enable_debug_log' => 'wcaf_enable_debug_log',
			'wmfo_enable_db_log' => 'wcaf_enable_db_log',
		);

		foreach ( $option_mappings as $old_option => $new_option ) {
			$old_value = get_option( $old_option );
			if ( $old_value !== false && get_option( $new_option ) === false ) {
				update_option( $new_option, $old_value );
			}
		}
	}

	/**
	 * Migrate order meta keys
	 */
	private static function migrate_order_meta() {
		global $wpdb;

		// Only migrate order meta if wc_get_order function is available
		if ( function_exists( 'wc_get_order' ) ) {
			// Migrate wmfo_fraud_attempts to wcaf_fraud_attempts
			$orders_with_old_meta = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s",
					'_wmfo_fraud_attempts'
				)
			);

			foreach ( $orders_with_old_meta as $meta_row ) {
				$order_id = $meta_row->post_id;
				$meta_value = $meta_row->meta_value;

				// Only update if new meta doesn't exist
				$order = wc_get_order( $order_id );
				if ( $order && ! $order->get_meta( '_wcaf_fraud_attempts' ) ) {
					$order->update_meta_data( '_wcaf_fraud_attempts', $meta_value );
					$order->save();
				}
			}
		}

		// Only migrate order meta if wc_get_order function is available
		if ( function_exists( 'wc_get_order' ) ) {
			// Migrate wmfo_skip_blacklist to wcaf_skip_blacklist
			$orders_with_skip_meta = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s",
					'wmfo_skip_blacklist'
				)
			);

			foreach ( $orders_with_skip_meta as $meta_row ) {
				$order_id = $meta_row->post_id;
				$meta_value = $meta_row->meta_value;

				// Only update if new meta doesn't exist
				$order = wc_get_order( $order_id );
				if ( $order && ! $order->get_meta( 'wcaf_skip_blacklist' ) ) {
					$order->update_meta_data( 'wcaf_skip_blacklist', $meta_value );
					$order->save();
				}
			}

			// Migrate _wmfo_cancelled to _wcaf_cancelled
			$orders_with_cancelled_meta = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s",
					'_wmfo_cancelled'
				)
			);

			foreach ( $orders_with_cancelled_meta as $meta_row ) {
				$order_id = $meta_row->post_id;
				$meta_value = $meta_row->meta_value;

				// Only update if new meta doesn't exist
				$order = wc_get_order( $order_id );
				if ( $order && ! $order->get_meta( '_wcaf_cancelled' ) ) {
					$order->update_meta_data( '_wcaf_cancelled', $meta_value );
					$order->save();
				}
			}
		}
	}

	/**
	 * Clean up old data after successful migration
	 */
	private static function cleanup_old_data() {
		global $wpdb;

		// Drop old tables after migration
		$old_logs_table = $wpdb->prefix . 'wmfo_logs';
		$old_fraud_table = $wpdb->prefix . 'wmfo_fraud_attempts';

		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $old_logs_table ) ) === $old_logs_table ) {
			$wpdb->query( "DROP TABLE {$old_logs_table}" );
		}

		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $old_fraud_table ) ) === $old_fraud_table ) {
			$wpdb->query( "DROP TABLE {$old_fraud_table}" );
		}

		// Delete old options
		$old_options = array(
			'wmfo_black_list_ips',
			'wmfo_black_list_names',
			'wmfo_black_list_phones',
			'wmfo_black_list_emails',
			'wmfo_black_list_email_domains',
			'wmfo_black_list_addresses',
			'wmfo_black_list_message',
			'wmfo_allow_blacklist_by_name',
			'wmfo_allow_blacklist_by_address',
			'wmfo_allow_blacklist_by_email_wildcard',
			'wmfo_black_list_allowed_fraud_attempts',
			'wmfo_black_list_product_types',
			'wmfo_black_list_order_status',
			'wmfo_white_listed_payment_gateways',
			'wmfo_white_listed_customers',
			'wmfo_enable_debug_log',
			'wmfo_enable_db_log',
		);

		foreach ( $old_options as $option ) {
			delete_option( $option );
		}
	}

	/**
	 * Check if migration is needed
	 */
	public static function needs_migration() {
		$current_version = get_option( self::MIGRATION_VERSION_KEY, '0' );
		return version_compare( $current_version, self::CURRENT_VERSION, '<' );
	}

	/**
	 * Get migration status
	 */
	public static function get_migration_status() {
		return array(
			'current_version' => get_option( self::MIGRATION_VERSION_KEY, '0' ),
			'target_version' => self::CURRENT_VERSION,
			'needs_migration' => self::needs_migration(),
		);
	}
}