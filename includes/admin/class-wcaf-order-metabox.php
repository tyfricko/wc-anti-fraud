<?php
/**
 * Adds an option on pending orders to skip the blacklist check:
 * "Check this to bypass this order payment from blacklisting".
 *
 * @package wc-anti-fraud
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Class WCAF_Order_MetaBox
 */
class WCAF_Order_MetaBox {

	/**
	 * WCAF_Order_MetaBox constructor.
	 */
	public function __construct() {
		// Meta-box to order edit page.
		add_action( 'add_meta_boxes_shop_order', array( $this, 'add_meta_box' ), 99, 1 );
		add_action( 'woocommerce_process_shop_order_meta', array( $this, 'save_order_meta_box_data' ), 99, 2 );
	}

	/**
	 * When an order's status is pending, register a metabox with the option:
	 * "Check this to bypass this order payment from blacklisting.".
	 *
	 * @hooked add_meta_boxes_shop_order
	 * @see register_and_do_post_meta_boxes()
	 *
	 * @param WP_Post $post The post object currently being edited.
	 */
	public function add_meta_box( $post ) {
		$order = wc_get_order( $post->ID );

		if ( ! ( $order instanceof WC_Order ) ) {
			return;
		}

		if ( 'pending' !== $order->get_status() ) {
			return;
		}
		add_meta_box(
			'wcaf-order-metabox',
			__( 'WCAF', 'wc-anti-fraud' ),
			array( $this, 'print_actions_meta_box' ),
			'shop_order',
			'side',
			'default'
		);
	}

	/**
	 * Output the HTML for the metabox. A checkbox:
	 * "Check this to bypass this order payment from blacklisting".
	 *
	 * @param WP_Post $post The post object currently being edited.
	 */
	public function print_actions_meta_box( $post ) {
		// Add a nonce field so we can check for it later.
		wp_nonce_field( 'wcaf_skip_blacklisting_nonce', 'wcaf_skip_blacklist_nonce' );

		$order = wc_get_order( $post->ID );
		$value = $order ? $order->get_meta( 'wcaf_skip_blacklist' ) : '';
		$html  = '<label for="wcaf_skip_blacklist">' . __( 'Check this to bypass this order payment from blacklisting.', 'wc-anti-fraud' ) . '</label>';
		$html .= '<input type="checkbox" name="wcaf_skip_blacklist" id="wcaf_skip_blacklist" style="margin: 4px 12px 0;"' . checked( $value, 'yes', false ) . ' value="yes" />';

		echo wp_kses(
			$html,
			array(
				'label' => array(
					'for' => array(),
				),
				'input' => array(
					'type'    => array(),
					'name'    => array(),
					'id'      => array(),
					'style'   => array(),
					'checked' => array(),
					'value'   => array(),
				),
			)
		);
	}

	/**
	 * When the post is saved, save our custom data.
	 *
	 * @hooked save_post
	 * @see wp_insert_post()
	 * @see wp_publish_post()
	 *
	 * @param int $post_id The id of the post (order) being edited.
	 */
	public function save_order_meta_box_data( int $post_id ) {
		// Check if our nonce is set.
		if ( ! isset( $_POST['wcaf_skip_blacklist_nonce'] ) ) {
			return;
		}

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wcaf_skip_blacklist_nonce'] ) ), 'wcaf_skip_blacklisting_nonce' ) ) {
			return;
		}

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check the user's permissions.
		if ( isset( $_POST['post_type'] ) && 'shop_order' === $_POST['post_type'] ) {

			if ( ! current_user_can( 'edit_page', $post_id ) ) {
				return;
			}
		} else {
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}
		}

		/* OK, it's safe for us to save the data now. */

		// Sanitize user input.
		$wcaf_skip_blacklist = isset( $_POST['wcaf_skip_blacklist'] ) ? sanitize_text_field( wp_unslash( $_POST['wcaf_skip_blacklist'] ) ) : null;

		// Update the meta field in the database.
		$order = wc_get_order( $post_id );
		if ( $order ) {
			$order->update_meta_data( 'wcaf_skip_blacklist', $wcaf_skip_blacklist );
			$order->save();
		}
	}
}

new WCAF_Order_MetaBox();
