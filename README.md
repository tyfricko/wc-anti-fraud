# WC Anti Fraud

[![WordPress plugin](https://img.shields.io/wordpress/plugin/v/wc-anti-fraud.svg)](https://wordpress.org/plugins/wc-anti-fraud/)
[![Tested up to WP 6.6](https://img.shields.io/wordpress/v/wc-anti-fraud.svg)](https://wordpress.org/plugins/wc-anti-fraud/)
[![Tested up to WC 10.1](https://img.shields.io/badge/WC-10.1-green.svg)](https://woocommerce.com/)
[![PHP 7.4+](https://img.shields.io/badge/PHP-7.4+-blue.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-GPLv2%2B-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

Advanced fraud detection and order management for WooCommerce with full HPOS compatibility. This is a rebranded and actively maintained fork of the original [Woo Manage Fraud Orders](https://wordpress.org/plugins/woo-manage-fraud-orders/) plugin, with updates starting from version 2.7.0 to ensure compatibility with modern WordPress, WooCommerce, and PHP versions.

## Overview

WC Anti Fraud provides comprehensive protection against fraudulent orders in your WooCommerce store. It allows administrators to blacklist suspicious customer details (email, phone, IP, address) and automatically tracks fraud attempts across payment gateways. The plugin integrates seamlessly with WooCommerce's High-Performance Order Storage (HPOS) for optimal performance.

### Key Features
- **Customer Blacklisting**: Block orders by email, phone, IP address, billing name, or full address (with wildcard support).
- **Automatic Fraud Detection**: Tracks failed payment attempts (e.g., credit card, eCheck) and auto-blacklists repeat offenders.
- **Order Management Integration**: Blacklist directly from order edit pages or bulk actions.
- **Whitelist Support**: Bypass checks for trusted payment gateways, emails, or WordPress user IDs.
- **Detailed Logging**: Comprehensive logs of fraud attempts and blocked orders.
- **HPOS Compatibility**: Fully supports WooCommerce's High-Performance Order Storage (requires WC 8.2+).
- **Store API & Blocks Checkout**: Compatible with WooCommerce 10+ block-based checkouts.
- **MVC Architecture**: Clean, maintainable code structure for developers.
- **Wildcard Blacklisting**: Use patterns like `%springfield%` for addresses or partial emails (e.g., `john` blocks john@gmail.com).

This plugin builds on the original Woo Manage Fraud Orders (last updated ~4 years ago on WordPress.org) by adding modern compatibility, security enhancements, and architectural improvements from version 2.7.0 onward.

## Requirements
- WordPress 5.0+
- WooCommerce 8.2+
- PHP 7.4+

## Installation

1. **Download the Plugin**:
   - Clone or download from GitHub: `git clone https://github.com/tyfricko/wc-anti-fraud.git`
   - Or download the ZIP release from the [Releases page](https://github.com/tyfricko/wc-anti-fraud/releases).

2. **Upload to WordPress**:
   - Upload the `wc-anti-fraud` folder to `/wp-content/plugins/` via FTP or the WordPress admin (Plugins > Add New > Upload Plugin).
   - Alternatively, use Composer if your setup supports it.

3. **Activate the Plugin**:
   - Go to **Plugins > Installed Plugins** in your WordPress admin.
   - Find "WC Anti Fraud" and click **Activate**.

4. **Configure Settings**:
   - Navigate to **WooCommerce > Settings > Anti Fraud** tab.
   - Configure blacklists, whitelists, and fraud detection options.
   - The plugin auto-declares HPOS compatibility on activation.

For detailed migration from the original plugin, see the [Migration Guide](INSTALL.md).

## Usage

### Blacklisting Customers
- **From Order Edit Page**:
  1. Edit a suspicious order in **WooCommerce > Orders**.
  2. In the "Order Actions" metabox, select **Blacklist Customer**.
  3. Update the order to apply.

- **Manual Blacklisting**:
  1. Go to **WooCommerce > Settings > Anti Fraud**.
  2. Add entries to the Email, Phone, IP, or Address blacklists (one per line).
  3. Use wildcards for patterns (e.g., `john` for emails containing "john"; `%Springfield%` for addresses).

- **Bulk Blacklisting**:
  1. In **WooCommerce > Orders**, select multiple orders.
  2. Choose **Bulk Actions > Blacklist Selected** and apply.

### Auto-Blacklisting
Enabled by default for gateways like Credit Card and eCheck. The plugin monitors failed orders and blacklists after a configurable number of attempts (default: 3).

### Whitelisting
- In settings, add trusted emails, user IDs, or payment gateways to bypass checks.
- Example: Whitelist `paypal` to allow all PayPal transactions.

### Viewing Logs
- **Fraud Attempts**: **WCAF > Fraud Attempt Logs** – View and manage tracked attempts.
- **Blocked Orders**: Check order notes for blacklist reasons.

### Checkout Behavior
Blocked customers see a customizable error message on checkout (e.g., "Your order cannot be processed due to security restrictions. Please contact support.").

## Configuration Options
Access via **WooCommerce > Settings > Anti Fraud**:

- **Blacklists**: Email, Phone, IP, Name, Address (with wildcard support).
- **Fraud Detection**: Max attempts before auto-blacklist, monitored gateways.
- **Order Status Blocking**: Prevent checkouts based on previous order statuses (e.g., Failed, Cancelled).
- **Whitelist**: Emails, User IDs, Payment Gateways.
- **Notifications**: Enable email alerts for blocked orders.
- **Debug Logging**: Toggle for troubleshooting (logs to `wp-content/debug.log`).

## Screenshots
1. [Anti Fraud Settings](screenshots/settings.png) – Configure blacklists and options.
2. [Checkout Block Message](screenshots/checkout-block.png) – User-facing error.
3. [Order Blacklist Action](screenshots/order-action.png) – Inline blacklisting.

*(Add actual screenshots to `/screenshots/` folder for GitHub rendering.)*

## Frequently Asked Questions

### Can I blacklist by ZIP code or city?
Yes, use partial addresses like "Springfield, US" or "90210".

### What if a legitimate customer gets blocked?
- Remove from blacklists in settings.
- Use whitelisting for trusted customers.
- Clear from fraud logs via **WCAF > Fraud Attempt Logs**.

### Is wildcard support available?
- Emails: Enter without `*` (e.g., "john" blocks john@any.com).
- Addresses: Use `%pattern%` (e.g., "%Springfield%").

### Does it work with WooCommerce Blocks Checkout?
Yes, fully compatible with WC 10+ Store API.

For more FAQs, see the [WordPress.org plugin page](https://wordpress.org/plugins/wc-anti-fraud/) (once submitted).

## Troubleshooting
- **HPOS Issues**: Ensure WC 8.2+. Run database migration if needed (auto-handled on activation).
- **No Blocking on Checkout**: Check if whitelists override or debug logging is disabled.
- **Compatibility Conflicts**: Deactivate other fraud/security plugins temporarily.
- **Logs Not Appearing**: Enable WP_DEBUG in `wp-config.php`.

If issues persist, open a [GitHub Issue](https://github.com/tyfricko/wc-anti-fraud/issues).

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for full details. Key updates since fork (v2.7.0+):

### 2.9.0 (Latest)
- Added Store API hooks for WC 10+ blocks compatibility.
- Fixed blacklist in blocks checkout.
- Code cleanup for production.

### 2.8.0
- Full WC 10+ HPOS compatibility.
- Refactored to MVC architecture.
- Added error handling for HPOS.

### 2.7.0
- Rebranded from "Woo Manage Fraud Orders".
- Updated for PHP 8.2, WP 6.4+, WC 10+.
- Improved HPOS with `wc_get_orders`.

*Original plugin (pre-2.7.0) changelog available on [WordPress.org](https://wordpress.org/plugins/woo-manage-fraud-orders/).*

## Contributing
See [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines on reporting bugs, submitting pull requests, and development setup.

## License
This plugin is licensed under the [GPLv2 or later](https://www.gnu.org/licenses/gpl-2.0.html).

## Support
- GitHub Issues: [Report bugs](https://github.com/tyfricko/wc-anti-fraud/issues)
- Documentation: This README and [WordPress.org](https://wordpress.org/plugins/wc-anti-fraud/)
- Author: [Matej Zlatič](https://matejzlatic.com)

---

&copy; 2025 Matej Zlatič. Based on original Woo Manage Fraud Orders by its authors.