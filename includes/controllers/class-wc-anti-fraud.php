<?php
/**
 * Main class
 * Handles everything from here, includes the file for the backend settings and
 * blacklisting funcitonalities, inlcudes the frontend handlers as well.
 *
 * @package wc-anti-fraud
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

if ( ! class_exists( 'WC_Anti_Fraud' ) ) {

	/**
	 * Class WC_Anti_Fraud
	 */
	class WC_Anti_Fraud {

		/**
		 * The current plugin version.
		 *
		 * @var string $version
		 */
		public $version = '2.9.1';

		/**
		 * Store the class singleton.
		 *
		 * @var ?WC_Anti_Fraud
		 */
		protected static $instance = null;

		/**
		 * Instantiate the class.
		 */
		protected function __construct() {
			$this->define_constants();
			$this->includes();
			$this->init_hooks();
			$this->init_components();
		}

		/**
		 * Get an instance of the class.
		 *
		 * @return WC_Anti_Fraud
		 */
		public static function instance(): self {
			if ( self::$instance === null ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Define constants
		 */
		private function define_constants() {
			$upload_dir = wp_upload_dir( null, false );

			$this->define( 'WCAF_ABSPATH', dirname( WCAF_PLUGIN_FILE ) . '/' );
			$this->define( 'WCAF_PLUGIN_BASENAME', plugin_basename( WCAF_PLUGIN_FILE ) );
			$this->define( 'WCAF_VERSION', $this->version );
			$this->define( 'WCAF_LOG_DIR', $upload_dir['basedir'] . '/wcaf-logs/' );
		}

		/**
		 * Check if WooCommerce HPOS is enabled
		 *
		 * @return bool
		 */
		public static function is_hpos_enabled() {
			// Check if WooCommerce is active and loaded
			if ( ! function_exists( 'is_plugin_active' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
				return false;
			}

			// Use the correct OrderUtil method as documented
			if ( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) &&
			     method_exists( '\Automattic\WooCommerce\Utilities\OrderUtil', 'custom_orders_table_usage_is_enabled' ) ) {
				try {
					return \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
				} catch ( Exception $e ) {
					return false;
				}
			}

			// Fallback for older WooCommerce versions or when OrderUtil is not available
			return false;
		}

		/**
		 * Check WooCommerce version compatibility
		 *
		 * @param string $version Version to check against
		 * @return bool
		 */
		public static function is_wc_version_compatible( $version = '8.2' ) {
			if ( ! defined( 'WC_VERSION' ) ) {
				return false;
			}
			return version_compare( WC_VERSION, $version, '>=' );
		}

		/**
		 * Define a constant if it has not already been defined.
		 *
		 * @param string $name The name of the constant to define.
		 * @param mixed $value The value of the constant.
		 */
		private function define( $name, $value ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}

		/**
		 * Init hooks
		 */
		private function init_hooks() {
			register_activation_hook( WCAF_PLUGIN_FILE, array( $this, 'install' ) );

			add_filter( 'plugin_action_links_' . plugin_basename( WCAF_PLUGIN_FILE ), array(
				$this,
				'action_links'
			), 99, 1 );
			add_action( 'plugins_loaded', array( $this, 'load_text_domain' ) );
			add_action( 'init', array( $this, 'may_be_create_log_dir_db_table' ) );
			add_action( 'admin_menu', array( $this, 'init_sub_menu' ), 9999 );
			add_action( 'plugins_loaded', array($this, 'fix_old_values'), 9999 );
		}

		/**
		 * Check is WooCommerce active.
		 * Create log dir
		 * Create log db table
		 */
		public function install() {
			try {
				// Check HPOS compatibility status (only if WooCommerce is loaded)
				if ( defined( 'WC_VERSION' ) && self::is_wc_version_compatible( '8.2' ) ) {
					if ( self::is_hpos_enabled() ) {
						// HPOS is enabled and plugin is compatible
					} else {
						// HPOS is available but not enabled
					}
				} elseif ( defined( 'WC_VERSION' ) ) {
					// WooCommerce version may not support HPOS features
				} else {
					// WooCommerce not fully loaded during activation - HPOS check deferred
				}
			} catch ( Exception $e ) {
				// Error during activation
			}

			if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
				require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
			}

			// multisite
			if ( is_multisite() ) {
				// this plugin is network activated - Woo must be network activated
				if ( is_plugin_active_for_network( plugin_basename( __FILE__ ) ) ) {
					$need = ! is_plugin_active_for_network( 'woocommerce/woocommerce.php' );
					// this plugin is locally activated - Woo can be network or locally activated
				} else {
					$need = ! is_plugin_active( 'woocommerce/woocommerce.php' );
				}
				// this plugin runs on a single site
			} else {
				$need = ! is_plugin_active( 'woocommerce/woocommerce.php' );
			}

			if ( $need ) {

				echo sprintf( esc_html__( 'WC Anti Fraud depends on %s to work!', 'wc-anti-fraud' ), '<a href="https://wordpress.org/plugins/woocommerce/" target="_blank">' . esc_html__( 'WooCommerce', 'wc-anti-fraud' ) . '</a>' );
				@trigger_error( '', E_USER_ERROR );

			}

			$this->may_be_create_log_dir_db_table();

		}

		/**
		 * Function to handle the creation of debug folder and DB table
		 */
		public function may_be_create_log_dir_db_table() {
			require_once plugin_dir_path( WCAF_PLUGIN_FILE ) . 'includes/class-wcaf-activator.php';

			WCAF_Activator::create_db_table();

			WCAF_Activator::create_upload_dir();

		}

		/**
		 * Add the `Settings` link under the plugin name on plugins.php.
		 *
		 * @hooked plugin_action_links_{plugin_basename}
		 *
		 * @param array<string, string> $actions The existing registered links.
		 *
		 * @return array<string, string>
		 * @see WP_Plugins_List_Table::single_row()
		 *
		 */
		public static function action_links( $actions ): array {

			$new_actions = array(
				'settings' => '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=settings_tab_wcaf' ) . '">' . __( 'Settings', 'wc-anti-fraud' ) . '</a>',
				'logs' => '<a href="' . admin_url( 'admin.php?page=wcaf' ) . '">' . __( 'Logs', 'wc-anti-fraud' ) . '</a>',
			);

			return array_merge( $new_actions, $actions );
		}

		/**
		 * Load text domain for translation
		 *
		 * @hooked plugins_loaded
		 */
		public function load_text_domain() {
			load_plugin_textdomain(
				'wc-anti-fraud',
				false,
				dirname( plugin_basename( __FILE__ ) ) . '/languages/'
			);
		}

		/**
		 * Include required files.
		 */
		public function includes() {
			require_once WCAF_ABSPATH . 'includes/wcaf-functions.php';
			require_once WCAF_ABSPATH . 'includes/models/class-wcaf-blacklist-handler.php';
			require_once WCAF_ABSPATH . 'includes/class-wcaf-debug-log.php';
			require_once WCAF_ABSPATH . 'includes/models/class-wcaf-track-fraud-attempts.php';
			require_once WCAF_ABSPATH . 'includes/class-wcaf-logs-handler.php';
			require_once WCAF_ABSPATH . 'includes/class-wcaf-fraud-attempts-db-handler.php';
			require_once WCAF_ABSPATH . 'includes/class-wcaf-deactivator.php';
			if ( is_admin() ) {
				require_once WCAF_ABSPATH . 'includes/admin/class-wcaf-settings-tab.php';
				require_once WCAF_ABSPATH . 'includes/admin/class-wcaf-order-metabox.php';
				require_once WCAF_ABSPATH . 'includes/admin/class-wcaf-order-actions.php';
				require_once WCAF_ABSPATH . 'includes/admin/class-wcaf-bulk-blacklist.php';
			}
		}

		/**
		 * Initialize plugin components
		 */
		public function init_components() {
			// Initialize components that need WooCommerce to be loaded
			add_action( 'woocommerce_loaded', function() {
				// Initialize fraud tracking
				if ( class_exists( 'WCAF_Track_Fraud_Attempts' ) ) {
					WCAF_Track_Fraud_Attempts::instance();
				}

				// Initialize order actions (admin only)
				if ( is_admin() && class_exists( 'WCAF_Order_Actions' ) ) {
					WCAF_Order_Actions::instance();
				}

				// Run migrations if needed
				if ( class_exists( 'WCAF_Migration_Handler' ) ) {
					WCAF_Migration_Handler::run_migrations();
				}
			} );

			// Fallback: Initialize immediately if WooCommerce is already loaded
			if ( did_action( 'woocommerce_loaded' ) ) {
				$this->initialize_fraud_tracking();
			}
		}

		/**
		 * Initialize fraud tracking components
		 */
		public function initialize_fraud_tracking() {
			// Initialize fraud tracking
			if ( class_exists( 'WCAF_Track_Fraud_Attempts' ) ) {
				try {
					WCAF_Track_Fraud_Attempts::instance();
				} catch ( Exception $e ) {
					// Handle exception silently in production
				}
			}

			// Initialize order actions (admin only)
			if ( is_admin() && class_exists( 'WCAF_Order_Actions' ) ) {
				try {
					WCAF_Order_Actions::instance();
				} catch ( Exception $e ) {
					// Handle exception silently in production
				}
			}

			// Run migrations if needed
			if ( class_exists( 'WCAF_Migration_Handler' ) ) {
				try {
					WCAF_Migration_Handler::run_migrations();
				} catch ( Exception $e ) {
					// Handle exception silently in production
				}
			}
		}

		/**
		 * Plugin submenus
		 */
		public function init_sub_menu() {
			add_menu_page( __( 'WCAF', 'wc-anti-fraud' ), __( 'WCAF', 'wc-anti-fraud' ), 'manage_options', 'wcaf', '', 'dashicons-welcome-write-blog', 59 );

			add_submenu_page( 'wcaf', __( 'Blocked Logs', 'wc-anti-fraud' ), __( 'Blocked Logs', 'wc-anti-fraud' ),
				'manage_options', 'wcaf', array( $this, 'render_wcaf_logs' ), 1 );

			add_submenu_page( 'wcaf', __( 'Fraud Attempt Logs', 'wc-anti-fraud' ), __( 'Fraud Attempt Logs', 'wc-anti-fraud' ),
				'manage_options', 'wcaf-fraud-attempts-logs', array( $this, 'render_fraud_attempts_logs' ), 2 );
		}

		/**
		 * This is not the blacklisted customer details.
		 * Rather, It is the list of customers who could not manage to place order due to blacklisting.
		 */
		public function render_wcaf_logs() {
			require_once plugin_dir_path( WCAF_PLUGIN_FILE ) . 'includes/admin/class-wcaf-logs-table.php';
			$logs = new WCAF_Logs_Table();
			$logs->prepare_items();
			?>
		           <div class="wrap">
		               <form method="post">
		                   <h2><?php _e( 'Blacklisted Log records.', 'wc-anti-fraud' ) ?></h2>
		                   <p><?php _e( 'This is not the blacklisted customer details. Rather,  It is the list of customers who could not manage to place an order due to blacklisting.', 'wc-anti-fraud' ); ?></p>
					<?php $logs->display(); ?>
		               </form>
		           </div>
			<?php
		}

		/**
		 * Fraud attempts logs
		 * Will be useful for handling SERVER side checking of fraud attempts
		 */
		public function render_fraud_attempts_logs() {
			require_once plugin_dir_path( WCAF_PLUGIN_FILE ) . 'includes/admin/class-wcaf-fraud-attempts-table.php';
			$logs = new WCAF_Fraud_Attempts_Table();
			$logs->prepare_items();
			?>
		           <div class="wrap">
		               <form method="post">
		                   <h2><?php _e( 'Fraud Order Attempts Log Records.', 'wc-anti-fraud' ) ?></h2>
		                   <p><?php _e( 'Every time there is failed order creation, the customer details will be recorded here.', 'wc-anti-fraud' ); ?></p>
					<?php $logs->display(); ?>
		               </form>
		           </div>
			<?php
		}

		public function fix_old_values(){
			$wcaf_white_listed_customers = get_option('wcaf_white_listed_customers');

			if(is_array($wcaf_white_listed_customers)){
				$new_wcaf_white_listed_customers = implode(PHP_EOL, $wcaf_white_listed_customers);

				update_option('wcaf_white_listed_customers', $new_wcaf_white_listed_customers);
			}
		}
	}
}
