# Installation Guide for WC Anti Fraud

This guide provides detailed instructions for installing and configuring the WC Anti Fraud plugin in your WooCommerce store. WC Anti Fraud is a rebranded and actively maintained fork of the original [Woo Manage Fraud Orders](https://wordpress.org/plugins/woo-manage-fraud-orders/) plugin, with all updates from version 2.7.0 onward focusing on modern compatibility, security enhancements, and performance improvements.

## Prerequisites

Before installing, ensure your environment meets these requirements:
- **WordPress**: 5.0 or higher (tested up to 6.6)
- **WooCommerce**: 8.2 or higher (tested up to 10.1) – Required for HPOS compatibility
- **PHP**: 7.4 or higher (supports up to PHP 8.2+)
- **Database**: MySQL 5.6+ or MariaDB 10.1+
- **Server**: Apache or Nginx with mod_rewrite enabled (for permalinks)

If migrating from the original Woo Manage Fraud Orders (last updated ~4 years ago), ensure your WooCommerce is updated to 8.2+ for full compatibility.

## Installation Methods

### Method 1: WordPress Admin (Recommended for Beginners)
1. Log in to your WordPress admin dashboard.
2. Navigate to **Plugins > Add New**.
3. Click **Upload Plugin** and select the `wc-anti-fraud.zip` file (download from GitHub Releases or WordPress.org).
4. Click **Install Now**, then **Activate Plugin**.
5. Proceed to configuration (see below).

### Method 2: Manual Upload via FTP
1. Download the plugin ZIP from [GitHub Releases](https://github.com/tyfricko/wc-anti-fraud/releases) or WordPress.org.
2. Unzip the file to reveal the `wc-anti-fraud` folder.
3. Using an FTP client (e.g., FileZilla), upload the `wc-anti-fraud` folder to `/wp-content/plugins/`.
4. In WordPress admin, go to **Plugins > Installed Plugins**, find "WC Anti Fraud", and click **Activate**.

### Method 3: Git Clone (For Developers)
1. Navigate to your plugins directory via SSH or terminal:
   ```
   cd /path/to/wordpress/wp-content/plugins/
   ```
2. Clone the repository:
   ```
   git clone https://github.com/tyfricko/wc-anti-fraud.git
   ```
3. In WordPress admin, activate the plugin as in Method 1.

### Method 4: Composer (Advanced)
If your setup uses Composer:
1. Add to `composer.json` in your theme or mu-plugins:
   ```
   "repositories": [
       {
           "type": "vcs",
           "url": "https://github.com/tyfricko/wc-anti-fraud"
       }
   ],
   "require": {
       "matejzlatic/wc-anti-fraud": "^2.9.0"
   }
   ```
2. Run `composer update` and activate in WordPress admin.

## Post-Installation Configuration

After activation:

1. **Access Settings**:
   - Go to **WooCommerce > Settings > Anti Fraud** tab.
   - This is where you'll configure blacklists, whitelists, and fraud detection rules.

2. **Initial Setup**:
   - **Enable Fraud Detection**: Toggle on automatic blacklisting for payment gateways (default: Credit Card, eCheck).
   - **Set Max Attempts**: Configure the number of failed attempts before auto-blacklisting (default: 3).
   - **Order Status Blocking**: Select statuses like "Failed" or "Cancelled" to prevent repeat orders.
   - **Whitelist Trusted Items**: Add reliable emails, user IDs, or gateways (e.g., `paypal`) to bypass checks.

3. **HPOS Migration (If Applicable)**:
   - If using WooCommerce High-Performance Order Storage (HPOS, WC 8.2+):
     - The plugin auto-declares compatibility on activation.
     - For existing data from the original plugin, run the built-in migration:
       1. Go to **WCAF > Tools** (if available) or check order edit pages.
       2. The plugin includes handlers to convert legacy metadata to HPOS format.
     - No manual intervention needed for new installations.
   - Verify: Edit an order and ensure blacklist actions appear without errors.

4. **Test the Setup**:
   - Create a test order with a blacklisted email (e.g., add `test@fraud.com` to email blacklist).
   - Attempt checkout – you should see the block message.
   - Check logs at **WCAF > Fraud Attempt Logs**.

5. **Custom Block Message**:
   - In settings, customize the checkout error: "Your order has been blocked for security reasons. Please contact support."

## Migrating from Original Woo Manage Fraud Orders

If upgrading from the original plugin (versions 1.0.0 - 2.6.x):

1. **Deactivate Old Plugin**:
   - Backup your database first.
   - Deactivate and delete "Woo Manage Fraud Orders".

2. **Install WC Anti Fraud**:
   - Follow installation methods above.
   - Blacklist data from the original plugin (stored in options like `wpmf_blacklist_email`) will be automatically migrated on activation.

3. **Data Migration**:
   - Emails, phones, IPs, and addresses transfer seamlessly.
   - Fraud logs may need manual review – old logs are in `wp_wpmf_fraud_attempts`; new ones use `wp_wcaf_fraud_attempts`.
   - Run any pending DB updates via **WCAF > Status > Run Updates**.

4. **Compatibility Notes**:
   - Updates from v2.7.0 include rebranding, PHP 8.2 support, and HPOS integration.
   - If issues arise (e.g., old order meta), use the migration handler in `includes/class-wcaf-migration-handler.php`.

## Troubleshooting Common Issues

- **Plugin Not Activating**:
  - Check PHP version: Ensure 7.4+ via hosting panel.
  - Error logs: Enable `WP_DEBUG` in `wp-config.php` and check `/wp-content/debug.log`.

- **Blacklisting Not Working**:
  - Verify WooCommerce 8.2+ and HPOS status (WooCommerce > Status > Tables).
  - Test with simple blacklist (e.g., IP `127.0.0.1`).
  - Conflicts: Deactivate other security/fraud plugins.

- **HPOS Errors**:
  - Update WooCommerce to latest.
  - Run: `wp wc tool run regenerate_order_lookups` (via WP-CLI) if available.

- **Logs Empty**:
  - Enable debug in settings.
  - Check database tables: `wp_wcaf_blacklist_*` and `wp_wcaf_fraud_attempts`.

- **Checkout Blocks Not Blocking (WC 10+)**:
  - Ensure Store API hooks are active (v2.9.0+).
  - Test with classic checkout first.

For persistent issues, check the [GitHub Issues](https://github.com/tyfricko/wc-anti-fraud/issues) or original plugin support forums.

## Next Steps

- Review [Usage Guide](USAGE.md) for advanced configuration.
- Explore [Contributing Guidelines](CONTRIBUTING.md) if developing extensions.

---

&copy; 2025 Matej Zlatič. Licensed under GPLv2 or later.