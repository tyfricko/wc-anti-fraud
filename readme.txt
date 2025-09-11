=== WC Anti Fraud ===
Contributors: matejzlatic
Tags: Blacklist customers, Anti Fraud orders, Tracking fraud attempts, Prevent fake orders, Blacklist fraud customers, HPOS, High-Performance Order Storage, WooCommerce Blocks
Requires at least: 5.0
Tested up to: 6.6
Stable tag: 2.9.0
Requires PHP: 7.4
WC requires at least: 8.2
WC tested up to: 10.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Advanced fraud detection and order management for WooCommerce with full HPOS and Blocks Checkout compatibility. This is a rebranded and actively maintained fork of the original [Woo Manage Fraud Orders](https://wordpress.org/plugins/woo-manage-fraud-orders/) plugin, with significant updates starting from version 2.7.0 to support modern WordPress, WooCommerce 10+, PHP 8.2, and enhanced security features.

== Description ==
An advanced fraud detection and order management extension for WooCommerce that provides comprehensive protection against fraudulent orders. The plugin allows administrators to blacklist customer details including billing phone, email, and IP addresses through an intuitive settings interface or directly from order edit pages.

Key features include:
- Intelligent tracking of fraud attempts across payment gateways (Credit Card, eCheck).
- Automatic blacklisting of repeat offenders based on failed attempts.
- Manual and bulk blacklisting from order management interfaces.
- Wildcard support for emails (e.g., "john" blocks john@any.com) and addresses (e.g., "%Springfield%").
- Whitelisting for trusted payment gateways, emails, or user IDs.
- Detailed logging of fraud attempts and blocked orders.
- Prevention of orders based on previous statuses (e.g., Failed, Cancelled).
- Full integration with WooCommerce Blocks Checkout and Store API (WC 10+).

**Full WooCommerce HPOS (High-Performance Order Storage) Compatibility**: Built for WooCommerce 8.2+, this plugin seamlessly works with HPOS for high-volume stores, including automatic migration handling for existing data.

Advanced fraud detection and order management for WooCommerce. <a href="https://matejzlatic.com" target="_blank">Learn more about the fork and updates</a>

For the original plugin's history (versions 1.0.0 - 2.6.x), refer to the <a href="https://wordpress.org/plugins/woo-manage-fraud-orders/" target="_blank">WordPress.org page</a>.

== HPOS Compatibility ==
This plugin is fully compatible with WooCommerce's High-Performance Order Storage (HPOS) system introduced in WooCommerce 8.2+.

**Requirements:**
- WooCommerce 8.2 or higher
- WordPress 5.0 or higher
- PHP 7.4 or higher

**Migration Instructions:**
1. Ensure your WooCommerce installation is updated to version 8.2 or higher
2. The plugin will automatically declare HPOS compatibility during activation
3. No additional configuration is required - the plugin works seamlessly with both traditional and HPOS storage
4. For existing installations, the plugin includes migration handlers to update old order metadata to HPOS-compatible format

**Compatibility Notes:**
The plugin automatically detects and adapts to your WooCommerce configuration, whether using traditional order storage or HPOS.

== Installation ==

1. Download and Upload the plugin folder 'wc-anti-fraud' to the `/wp-content/plugins/` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the WooCommerce > Settings > Anti Fraud tab for the plugin settings and update the settings or you can go to the order edit page and choose the action "Blacklist order" for adding customer details into blacklists.
4. Then, you are done. It will block all the blacklisted customers from checking out.


WHAT IF A LEGITIMATE CUSTOMER IS BLOCKED?

* Navigate to WP-Admin >> WooCommerce >> WCAF Tab, remove the customer details from blacklisting.
* Navigate to WP-Admin >> WCAF >> Fraud Attempt Logs, and remove the customer details from fraud attempts logs.
* Whitelist the customer from WP-Admin >> WooCommerce >> WCAF Tab

== Frequently Asked Questions ==

= Can I add zipcode or city Address in the blacklist? =
Yes. This feature is available from version 2.1.0.

= Can I use email wildcard for blacklisting?
Yes, absolutely. This feature is available from version 2.4.0. You can enter the wildcard entry without an asterisk (*). E.g. If you put "john", It will block orders from every email containing the string "john", john@gmail.com, john@yahoo.com, johndoe@anyhting.com, avfjohndev@anything.com and so on.

= What is the basic rule for address blacklisting? =
The full syntax for block by address is,
“Street address 1, Address 2, City, State, ZIPCODE, Country”

Also, the partial address can also be used. For example, “Springfield, US” will block every order in every city named Springfield in the US;
Likewise, “90210” will block all orders to that zip code.

Similarly, “Springfield, IL” will block all the orders from an address in a city named “Springfield” in the state “Illinois”.

“Springfield” will block every order from “Springfield”.
“IL” will block every order from the state “IL”.
“US” will block orders from country “United States” and so on.

So, the very simple rule is, you can create any combination of address fields “Street address 1, Address 2, City, State, ZIPCODE, Country” separated by a comma as needed.

= How do we blacklist the customer's detail? =
There are multiple ways to manage customer blacklisting. First, open the order edit page, choose the option "Blacklist Customer" in the "order actions" section and update the order page and it will add the customer's details into blacklist. Second, navigate to WooCommerce > Settings > Anti Fraud tab. You will see comprehensive textarea fields for "Email", "Phones", "IP Address", and other fraud prevention settings. You can manually edit those settings to update the blacklisted customers.

= Is there wildcard rule for address blacklisting? =
Yes, There is. Wildcard must be in the format of "%address%"; enclosed by "%". For example; If you put the "%Springfield%" as a wildcard rule for address, It will block the order if there is any match of "Springfield" within any of customer\'s address(Street address, address line 2, city etc.)

= Is there auto blacklisting system as well? =
Yes, there is. But this auto system will be in action only for those payment gateways which authorizes the payment details and charge instantly (Electronic Check, Credit Card) etc.

= What is the process for automatic blacklisting system? =
Let us take an example of Electronic check payment gateway. When customer successfully validates the payment fields like "Route Number", "Account Number" & "Check Number" fields, those values will be sent for authorizing. If Bank couldn't authorize those customer details, the woo commerce will mark the order as "Failed". Then, same customer may try to create the multiple number of "Failed" orders. This plugin will track that behavior and blacklist the customer from future checkout.

= Can I remove the customer details from blacklist? =
Yes, absolutely. You can either edit the setting option in "WooCommerce > Settings > Anti Fraud" or you can do it from the order edit page under "Order Actions" option.

= Can I prevent the customer based on their previous order status? =
Yes, absolutely. You can choose multiple order statuses in the settings and this is completely compatible with <a href="https://woocommerce.com/products/woocommerce-order-status-manager/" target="_blank">WooCommerce Order Status Manager</a>.

= Can I whitelist customer details or anything? =
Yes. The plugin supports comprehensive whitelisting features. You can whitelist specific payment gateways, user email addresses, or WordPress user IDs to bypass fraud detection for trusted customers and payment methods.

== Screenshots ==

1. Anti Fraud Settings & Customer Management
2. Fraud Prevention Message in Checkout
3. Blacklisting Customer Details via Order Management

== Changelog ==

**Note**: This changelog covers updates from version 2.7.0 onward in the WC Anti Fraud fork. For the complete history of the original Woo Manage Fraud Orders plugin (versions 1.0.0 - 2.6.x), see the <a href="https://wordpress.org/plugins/woo-manage-fraud-orders/" target="_blank">original plugin page on WordPress.org</a>.

= 2.9.0 =
* Enhancement: Added Store API hooks for WooCommerce 10+ blocks checkout compatibility
* Enhancement: Improved blacklist functionality for both classic and blocks checkout
* Enhancement: Added comprehensive Store API and Blocks checkout validation
* Enhancement: Enhanced error handling and logging for checkout processes
* Enhancement: Code cleanup - removed all debug and test code for production readiness
* Enhancement: Updated plugin version to 2.9.0 for publication
* Fix: Resolved blacklist functionality not working in WooCommerce 10+ blocks checkout
* Security: Enhanced input validation and error handling

= 2.8.0 =
* Enhancement: Full WooCommerce 10+ compatibility with HPOS (High-Performance Order Storage)
* Refactor: Restructured plugin to MVC architecture (Models, Views, Controllers)
* Enhancement: Added comprehensive error handling and logging for HPOS operations
* Enhancement: Added version compatibility checks for WooCommerce and HPOS features
* Enhancement: Improved order meta operations with HPOS compatibility
* Security: Enhanced input validation and sanitization
* Test: Added HPOS compatibility test suite
* Update: Updated minimum WooCommerce version requirement to 8.2

= 2.7.0=
* Rebrand: Complete rebranding from "Woo Manage Fraud Orders" to "WC Anti Fraud"
* Compatibility: Updated for PHP 8.2, WordPress 6.4+, WooCommerce 10+
* Fix: Corrected text domain loading path
* Enhancement: Improved HPOS compatibility with wc_get_orders
* Security: Enhanced database operations with proper sanitization
* Update: Minimum PHP version requirement to 7.4

= 2.6.1=
* fix: balckilisting by email
* add: enable/disable option for email wildcard blocking

= 2.6.0=
* fix: possible server error in settings page section

= 2.5.6=
* fix: Optional checkout fields issue with blocking legitimate orders

= 2.5.5=
* Compatibility check WP 5.9.2
* Compatibility check WC 6.3.1

= 2.5.4=
* fix: typecast removal from functions

= 2.5.3=
* fix: bulk blacklisting with other third party plugins compatibility

= 2.5.2=
* fix: empty billing email fix

= 2.5.1=
* fix: wildcard email block

= 2.5.0=
* add: wildcard address blocking
* add: order cancelled metadata
* remove: cookie based blocking removal

= 2.4.1=
* fix: fatal error on order status change to "failed"
* fix: undefined checkout fields

= 2.4.0=
* feat: email blacklisting wildcard
* feat: fraud attempt tracked from SERVER

= 2.3.2=
* feat: enable/disable option for blacklisted by address

= 2.3.1 =
* fix: garbage clean: related to 2-3-bug-query-per-user-in-admin.

= 2.3.0 =
* feat: whitelisting by payment gateways and the specific users.

= 2.2.1 =
* fix: disabled checkout field with corresponding empty blacklisted options blocking the order placement

= 2.2.0 =
* feature: Debug log and DB log of order placement restriction

= 2.1.1 =
* fix: update on customer address
* fix: multisite plugin on active error
* Compatibility check WC 5.5.1

= 2.1.0 =
* feat: block by customer billing address

= 2.0.2 =
* fix: order get_type() on bool error check
* Compatibility check WC 5.4.1

= 2.0.1 =
* fix: fraud attempts
* Compatibility check WC 5.4.0

= 2.0.0 =
* feature: blacklisting by product types
* feature: skip blacklisting for order payment page
* Compatibility check WC 5.1.0

= 1.7.2 =
* fix: order status cancelled on blacklisting from backend
* Compatibility check WC 5.0.0

= 1.7.1 =
* add: compatibility check with eWay
* Compatibility check WC 4.9.2

= 1.7.0 =
* Feat: blacklisting with "order-pay" action
* Compatibility check WC 4.9.1

= 1.6.2 =
* Fixes to "conflict with WooCommerce payment hook" issued on github repo.
* "Removed from blacklist" order note added.
* "Bulk blacklist" safe redirect fix.

= 1.6.1 =
* Compatibility check with WP 5.6 & WC 4.8.0

= 1.6.0 =
* Compatibility check with WC 4.6.1
* Blocking by email domain

= 1.5.5 =
* Compatibility check with WP 5.5 & WC 4.4.1

= 1.5.4 =
* Compatibility check with WC 4.3.0

= 1.5.3 =
* Compatibility check with WC 4.2.2

= 1.5.2 =
* Order statuses multiselect setting UI change.
* Blacklist by order status bug fixes.

= 1.5.1 =
* Translation bug fixes.

= 1.5.0 =
* Bocking by customer name feature added.
* Compatibility check with WC 4.2.0.

= 1.4.9 =
* Compatibility check with WC 4.1.0.

= 1.4.8 =
* checkout blocking parameters fixes.

= 1.4.7 =
* default allowed fraud attempts fixed.

= 1.4.6 =
* bulk blacklisting notification fixes.

= 1.4.5 =
* Compatibility with woocommerce 3.7.0

= 1.4.3 =
* Compatibility with woocommerce 3.6.3.

= 1.4.1 =
* "Order Statuses" setting label bug fixed.

= 1.4.0 =
* Order placement prevention based on order status added.

= 1.3.0 =
* Feature of removing customer details from blacklists added. 
* Format of saving the blacklist option changed from comma separated to new line values
* DB update option added. 

= 1.2.2 =
* Translation text domain added.

= 1.2 =
* Bulk Blacklisting options added in orders listing page.

= 1.0.3 =
* Duplication of the blacklisted emails, phones and IPs removed.

= 1.0.2 =
* Minor bug fixed.

= 1.0.1 =
* Dependency check added.  

= 1.0.0 =
* First Version

== Upgrade Notice ==
= 2.4.0 =
Version 2.4.0 supports email wildcard blacklisting and fraud attempts tracking from SERVER.

= 2.3.0 =
Version 2.3.0 supports whitelisting features by payment gateways and the specific users.

= 2.2.0 =
Version 2.2.0 supports debug log and DB log of order placement restriction

= 2.1.0 =
Version 2.1.0 supports blacklisting by customer billing address

= 2.0.0 =
Version 2.0.0 supports blacklisting by product types, skipping blacklisting per order payment page

= 1.5.0 =
Version 1.5.0 supports blocking by customer name
