<?php
/**
 * Blacklist Settings
 *
 * @package wc-anti-fraud
 */

if ( ! class_exists( 'WCAF_Settings_Tab' ) ) {

	/**
	 * Class WCAF_Settings_Tab
	 */
	class WCAF_Settings_Tab {

		/**
		 * Bootstraps the class and hooks required actions & filters.
		 */
		public static function init() {
			add_filter( 'woocommerce_settings_tabs_array', array( self::class, 'add_settings_tab' ), 50 );
			add_action( 'woocommerce_settings_tabs_settings_tab_wcaf', array( self::class, 'settings_tab' ) );
			add_action( 'woocommerce_update_options_settings_tab_wcaf', array( self::class, 'update_settings' ) );
			add_filter( 'woocommerce_admin_settings_sanitize_option', array(
				self::class,
				'update_setting_filter'
			), 100, 3 );
		}

		/**
		 * Add a new settings tab to the WooCommerce settings tabs array.hp
		 *
		 * @hooked woocommerce_settings_tabs_array
		 *
		 * @param array<string, string> $settings_tabs Array of WooCommerce setting tabs & their labels, excluding the Subscription tab.
		 *
		 * @return array<string, string>
		 * @see WC_Admin_Settings::output()
		 * @see \Automattic\WooCommerce\Admin\Features\Navigation\CoreMenu::get_setting_items()
		 *
		 */
		public static function add_settings_tab( array $settings_tabs ) {
			$settings_tabs['settings_tab_wcaf'] = esc_html__( 'WCAF', 'wc-anti-fraud' );

			return $settings_tabs;
		}

		/**
		 * Uses the WooCommerce admin fields API to output settings via the @see woocommerce_admin_fields() function.
		 *
		 * @hooked woocommerce_settings_tabs_settings_tab_wcaf
		 *
		 * @uses woocommerce_admin_fields()
		 * @uses self::get_settings()
		 */
		public static function settings_tab() {
			woocommerce_admin_fields( self::get_settings() );
		}

		/**
		 * Uses the WooCommerce options API to save settings via the @see woocommerce_update_options() function.
		 *
		 * @hooked woocommerce_update_options_settings_tab_wcaf
		 *
		 * @uses woocommerce_update_options()
		 * @uses self::get_settings()
		 */
		public static function update_settings() {
			woocommerce_update_options( self::get_settings() );
		}

		/**
		 * Trim whitespace around each inputted value.
		 *
		 * @hooked woocommerce_admin_settings_sanitize_option
		 *
		 * @param mixed $value The value to sanitize and return.
		 * @param array<string, mixed> $option A single option as defined in this class.
		 * @param mixed $raw_value The $value before any previous filters changed it.
		 *
		 * @return mixed
		 * @see WC_Admin_Settings::save_fields()
		 *
		 * @see WMFO_Settings_Tab::get_settings()
		 *
		 */
		public static function update_setting_filter( $value, $option, $raw_value ) {
			if ( in_array(
				$option['id'],
				array(
					'wcaf_black_list_names',
					'wcaf_black_list_phones',
					'wcaf_black_list_emails',
					'wcaf_black_list_email_domains',
					'wcaf_black_list_ips',
					'wcaf_black_list_addresses',
				),
				true
			) ) {
				// check if there are duplication of blacklisted values.
				$value = implode( PHP_EOL, array_unique( array_map( 'trim', explode( PHP_EOL, $value ) ) ) );
			}

			return apply_filters( $option['id'] . '_option', $value, $option, $raw_value );
		}

		/**
		 * Get all the settings for this plugin.
		 *
		 * @return array<string, array<string, mixed>> Array of settings for WooCommerce to display.
		 * @see woocommerce_admin_fields() function.
		 *
		 */
		public static function get_settings() {
			$settings = array(
				'section_title'                     => array(
					'name' => esc_html__( 'Blacklisted Customers', 'wc-anti-fraud' ),
					'type' => 'title',
					'desc' => '',
					'id'   => 'wcaf_settings_title',
				),
				'blacklists_message'                => array(
					'name'     => esc_html__( 'Blacklists Notice Message', 'wc-anti-fraud' ),
					'css'      => 'width:600px;height:50px',
					'type'     => 'textarea',
					'default'  => esc_html__( 'Sorry, You are being restricted from placing orders.', 'wc-anti-fraud' ),
					'desc'     => esc_html__( 'Enter the message to be shown for blocked customers', 'wc-anti-fraud' ),
					'id'       => 'wcaf_black_list_message',
					'desc_tip' => true,
				),
				'blacklists_allowed_fraud_attempts' => array(
					'name'              => esc_html__( 'Number of allowed Fraud Attempts', 'wc-anti-fraud' ),
					'type'              => 'number',
					'css'               => 'width:50px',
					'default'           => 5,
					'desc'              => esc_html__( 'Enter the number of allowed fraud attempts before blocking automatically. It counts increases only if order status changes to failed on order placement.', 'wc-anti-fraud' ),
					'id'                => 'wcaf_black_list_allowed_fraud_attempts',
					'custom_attributes' => array(
						'min'  => 1,
						'step' => 1,
					),
					'desc_tip'          => esc_html__( 'This will block customers from placing an order if they try more than the specified number of attempts and the order still fails. Legitimate reasons for an order failing could be entering wrong credit card number or address verification mismatch. If the customer continues to try to complete the order, the order will be blocked and notice message sent after the specified number of retries.', 'wc-anti-fraud' ),
				),
				'whitelisted_payment_gateways'      => array(
					'name'     => esc_html__( 'Whitelisted Payment Gateways', 'wc-anti-fraud' ),
					'css'      => 'width:600px;height:auto',
					'type'     => 'multiselect',
					'class'    => 'wc-enhanced-select',
					'desc'     => esc_html__( 'You can select multiple payment gateways. Whitelist more priority than blacklists.', 'wc-anti-fraud' ),
					'options'  => wcafp_get_enabled_payment_gateways(),
					'id'       => 'wcaf_white_listed_payment_gateways',
					'desc_tip' => true,
				),
				'whitelisted_customers'             => array(
					'name'     => esc_html__( 'Whitelisted Customers', 'wc-anti-fraud' ),
					'css'      => 'width:600px;height:200px',
					'type'     => 'textarea',
					'desc'     => esc_html__( 'You can select multiple customers (by User ID or by User Email). New entry should be in new line. Whitelist has more priority than blacklists.', 'wc-anti-fraud' ),
					'id'       => 'wcaf_white_listed_customers',
					'desc_tip' => true,
				),
				'blacklists_order_status'           => array(
					'name'     => esc_html__( 'Blacklisted Order Statuses', 'wc-anti-fraud' ),
					'css'      => 'width:600px;height:auto',
					'type'     => 'multiselect',
					'class'    => 'wc-enhanced-select',
					'desc'     => esc_html__( 'You can select multiple order statuses.If customer has previous order in one of above selected order items, He/She will not be able to place order.', 'wc-anti-fraud' ),
					'options'  => wc_get_order_statuses(),
					'id'       => 'wcaf_black_list_order_status',
					'desc_tip' => true,
				),
				'blacklists_product_types'          => array(
					'name'     => esc_html__( 'Blacklisted Product Types', 'wc-anti-fraud' ),
					'css'      => 'width:600px;height:auto',
					'type'     => 'multiselect',
					'class'    => 'wc-enhanced-select',
					'desc'     => esc_html__( 'You can select multiple product types.', 'wc-anti-fraud' ),
					'options'  => wc_get_product_types(),
					'id'       => 'wcaf_black_list_product_types',
					'desc_tip' => esc_html__( 'If selected, customer will be blocked only if they have product of at least one selected product types in the cart.', 'wc-anti-fraud' ),
				),
				'allow_blacklist_by_name'           => array(
					'name'     => esc_html__( 'Allow blacklist by Name ?', 'wc-anti-fraud' ),
					'css'      => 'width:600px;height:200px',
					'type'     => 'checkbox',
					'default'  => 'no',
					'desc'     => esc_html__( 'Check this to blacklist customer by their name. Only enabling this will auto fill the "blacklisted Names" setting option below.', 'wc-anti-fraud' ),
					'id'       => 'wcaf_allow_blacklist_by_name',
					'desc_tip' => false,
				),
				'allow_blacklist_by_wildcard_email'           => array(
					'name'     => esc_html__( 'Allow blacklist by Email wildcard ?', 'wc-anti-fraud' ),
					'css'      => 'width:600px;height:200px',
					'type'     => 'checkbox',
					'default'  => 'no',
					'desc'     => esc_html__( 'Check this to blacklist customer by email wildcard.', 'wc-anti-fraud' ),
					'id'       => 'wcaf_allow_blacklist_by_email_wildcard',
					'desc_tip' => false,
				),
				'allow_blacklist_by_address'        => array(
					'name'     => esc_html__( 'Allow blacklist by Address ?', 'wc-anti-fraud' ),
					'css'      => 'width:600px;height:200px',
					'type'     => 'checkbox',
					'default'  => 'yes',
					'desc'     => esc_html__( 'Check this to blacklist customer by their address. Only enabling this will auto fill the "Blacklisted Address" setting option below.', 'wc-anti-fraud' ),
					'id'       => 'wcaf_allow_blacklist_by_address',
					'desc_tip' => false,
				),
				'enable_debug_log'                  => array(
					'name'     => esc_html__( 'Enable debug log', 'wc-anti-fraud' ),
					'css'      => 'width:600px;height:200px',
					'type'     => 'checkbox',
					'default'  => 'no',
					'desc'     => esc_html__( 'Check this to enable the debug log. On checking this, Everytime customer encounters the "blocked" message, It will dump details at path /wp-content/uploads/wcaf-logs/ . ', 'wc-anti-fraud' ),
					'id'       => 'wcaf_enable_debug_log',
					'desc_tip' => false,
				),
				'enable_db_log'                     => array(
					'name'     => esc_html__( 'Enable DB log', 'wc-anti-fraud' ),
					'css'      => 'width:600px;height:200px',
					'type'     => 'checkbox',
					'default'  => 'yes',
					'desc'     => esc_html__( 'Check this to enable the DB log. On checking this, Everytime customer encounters the "blocked" message, It will log the details to the DB table. It can be viewed Woocommerce>WCAF Logs. ', 'wc-anti-fraud' ),
					'id'       => 'wcaf_enable_db_log',
					'desc_tip' => false,
				),
				'blacklists_names'                  => array(
					'name'     => __( 'Blacklisted Names', 'wc-anti-fraud' ),
					'css'      => 'width:600px;height:200px',
					'type'     => 'textarea',
					'desc'     => esc_html__( 'Enter combined first name and last name in new line. Eg. "John Doe"', 'wc-anti-fraud' ),
					'id'       => 'wcaf_black_list_names',
					'desc_tip' => false,
				),
				'blacklists_phones'                 => array(
					'name'     => esc_html__( 'Blacklisted Phones', 'wc-anti-fraud' ),
					'css'      => 'width:600px;height:200px',
					'type'     => 'textarea',
					'desc'     => esc_html__( 'Enter Phones in new line', 'wc-anti-fraud' ),
					'id'       => 'wcaf_black_list_phones',
					'desc_tip' => false,
				),
				'blacklists_emails'                 => array(
					'name'     => esc_html__( 'Blacklisted Emails', 'wc-anti-fraud' ),
					'css'      => 'width:600px;height:200px',
					'type'     => 'textarea',
					'desc'     => esc_html__( 'Enter Emails in new line. You can enter the wildcard entry without an asterisk (*). Eg. If you put "john", It will block orders from every email containing the string "john", john@gmail.com, john@yahoo.com, johndoe@anyhting.com, avfjohndev@anything.com and so on.', 'wc-anti-fraud' ),
					'id'       => 'wcaf_black_list_emails',
					'desc_tip' => false,
				),
				'blacklists_email_domains'          => array(
					'name'     => esc_html__( 'Blacklisted Email Domains.', 'wc-anti-fraud' ),
					'css'      => 'width:600px;height:200px',
					'type'     => 'textarea',
					'desc'     => esc_html__( 'Enter email domain in new line. Example "mailnator.com"', 'wc-anti-fraud' ),
					'id'       => 'wcaf_black_list_email_domains',
					'desc_tip' => false,
				),
				'blacklists_ips'                    => array(
					'name'     => esc_html__( 'Blacklisted IP Addresses', 'wc-anti-fraud' ),
					'css'      => 'width:600px;height:200px',
					'type'     => 'textarea',
					'desc'     => esc_html__( 'Enter IPs in new line', 'wc-anti-fraud' ),
					'id'       => 'wcaf_black_list_ips',
					'desc_tip' => false,
				),
				'blacklists_addresses'              => array(
					'name'     => esc_html__( 'Blacklisted Billing/Shipping Addresses', 'wc-anti-fraud' ),
					'css'      => 'width:600px;height:200px',
					'type'     => 'textarea',
					'desc'     => esc_html__( 'Enter one address per line, with each line of the address itself separated by a comma. Partial addresses can be used, e.g. "Springfield, US" will block every order in every town named Springfield in the US; "90210" will block all orders to that zip code. And Wildcard must be in the format of "%address%"; enclosed by "%". For example; If you put the "%Springfield%" as a wildcard rule for address, It will block the order if there is any match of "Springfield" within any of customer\'s address(Street address, address line 2, city etc.).', 'wc-anti-fraud' ),
					'id'       => 'wcaf_black_list_addresses',
					'desc_tip' => false,
				),
				'section_end'                       => array(
					'type' => 'sectionend',
					'id'   => 'wcaf_settings_section_end',
				),
			);

			return apply_filters( 'wcaf_settings', $settings );
		}
	}
}
// init the Settings class.
WCAF_Settings_Tab::init();

/**
 * Admin Styling
 */
add_action(
	'admin_head',
	function () {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['tab'] ) && 'settings_tab_wcaf' === $_GET['tab'] ) : ?>
            <style>
                .wrap.woocommerce .forminp.forminp-multiselect span.description {
                    display: block;
                    padding: 10px 0 0;
                }
            </style>
		<?php
		endif;
	}
);
