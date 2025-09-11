# Usage Guide for WC Anti Fraud

This guide details how to use the WC Anti Fraud plugin for fraud prevention in your WooCommerce store. As a rebranded fork of the original [Woo Manage Fraud Orders](https://wordpress.org/plugins/woo-manage-fraud-orders/), this documentation covers features from version 2.7.0 onward, including modern WooCommerce compatibility and enhanced functionality.

## Core Concepts

WC Anti Fraud operates by:
- **Blacklisting**: Blocking specific customer identifiers (email, phone, IP, name, address) at checkout.
- **Auto-Detection**: Monitoring failed orders and auto-blacklisting repeat fraud attempts.
- **Logging**: Recording all attempts and blocks for analysis.
- **Whitelisting**: Bypassing checks for trusted entities.

The plugin integrates with WooCommerce hooks, so it works seamlessly with classic and block-based checkouts (WC 10+).

## Blacklisting Customers

### Manual Blacklisting via Settings
1. Go to **WooCommerce > Settings > Anti Fraud**.
2. In the respective fields, add one entry per line:
   - **Emails**: `fraud@example.com` or wildcard `fraud` (blocks any email containing "fraud").
   - **Phones**: `+1234567890` or partial `123` (blocks numbers containing "123").
   - **IPs**: `192.168.1.1` or range `192.168.1.*`.
   - **Names**: `John Doe` (blocks billing first/last name matches).
   - **Addresses**: Full `"123 Main St, Springfield, IL 62701, US"` or partial `"Springfield, IL"`. Wildcard: `"%Springfield%"`.

3. Save changes. Blocked customers see: "Order blocked for security reasons."

### Blacklisting from Order Pages
- **Single Order**:
  1. Edit order in **WooCommerce > Orders**.
  2. In "Order Actions", select **Blacklist Customer**.
  3. Update – adds all customer details (email, phone, IP, address) to blacklists.
  4. Order note: "Customer blacklisted due to fraud suspicion."

- **Bulk Blacklisting**:
  1. In Orders list, select multiple suspicious orders.
  2. Bulk Actions > **Blacklist Selected**.
  3. Apply – processes all selected without individual review.

### Address Blacklisting Examples
- Block entire city: `Springfield, US`
- Block ZIP: `90210`
- Block state: `IL`
- Wildcard city: `%Springfield%` (matches anywhere in address)
- Full address: `123 Fake St, Fraudville, CA 90210, US`

**Tip**: For partial matches, test in a staging environment to avoid blocking legitimate customers.

## Automatic Fraud Detection

Enabled by default for instant-authorization gateways (Credit Card, eCheck).

### How It Works
1. Customer attempts payment; bank rejects (order status: Failed).
2. Plugin logs the attempt with customer details.
3. On subsequent attempts, if failures exceed threshold (default: 3), auto-blacklist.
4. Order auto-cancels if configured; email alert sent to admin.

### Configuration
- **Settings > Anti Fraud > Fraud Detection**:
  - Max Attempts: 3 (adjust 1-10).
  - Monitored Gateways: Select Credit Card, eCheck, etc.
  - Auto-Cancel Orders: Yes/No.
  - Notification Email: Your admin email.

### Example Scenario
- Customer tries eCheck 4 times (fails each).
- After 3rd fail: Log entry in **WCAF > Fraud Attempt Logs**.
- 4th attempt: Auto-blacklist email/phone/IP; order cancelled.

## Whitelisting Trusted Customers

Bypass fraud checks for reliable entities.

### Setup
In **Settings > Anti Fraud > Whitelist**:
- **Emails**: `vip@trusted.com` (exact) or `vip` (wildcard).
- **User IDs**: `5, 10` (comma-separated WordPress users).
- **Payment Gateways**: `paypal, stripe` (ID or slug).
- **IPs**: `trusted.ip.range.*`

### Use Cases
- VIP customers: Whitelist their emails.
- Preferred gateways: Allow all PayPal transactions.
- Internal testing: Whitelist dev IPs.

**Priority**: Whitelists override blacklists. A whitelisted email can checkout even if blacklisted elsewhere.

## Managing Logs and Data

### Viewing Fraud Logs
- **WCAF > Fraud Attempt Logs**: Table of attempts with filters (date, email, IP).
  - Columns: Timestamp, Customer Email, Phone, IP, Attempts Count, Action (Blacklisted?).
  - Actions: Delete entry, Manual blacklist.

### Blocked Orders Log
- Check order notes in **WooCommerce > Orders** for "Blocked by WC Anti Fraud" reasons.
- Debug logs (if enabled): `/wp-content/debug.log` with entries like `[WCAF] Blocked IP 192.168.1.1`.

### Data Cleanup
- **Remove from Blacklist**: Edit settings or use order action "Remove Blacklist".
- **Clear Logs**: Bulk delete in Fraud Logs table.
- **DB Tables** (advanced): `wp_wcaf_blacklist_email`, `wp_wcaf_fraud_attempts` (use phpMyAdmin or WP-CLI).

## Advanced Usage

### Order Status-Based Blocking
- In settings, select statuses: Failed, Cancelled, Refunded.
- Example: Block customers with 2+ previous "Failed" orders.
- Compatible with [WooCommerce Order Status Manager](https://woocommerce.com/products/woocommerce-order-status-manager/).

### Customizing Block Messages
Add to `functions.php`:
```php
add_filter('wcaf_checkout_block_message', function($message) {
    return 'Contact support@yourstore.com for assistance.';
});
```

### Developer Hooks
- **Pre-Checkout Validation**:
  ```php
  add_filter('wcaf_is_blacklisted', function($is_blacklisted, $customer_data) {
      // Custom logic, e.g., allow if order total > $1000
      if ($customer_data['total'] > 1000) {
          return false;
      }
      return $is_blacklisted;
  }, 10, 2);
  ```

- **Auto-Blacklist Threshold**:
  ```php
  add_filter('wcaf_max_fraud_attempts', function($max) {
      return 5; // Increase to 5 attempts
  });
  ```

- **Log Custom Events**:
  ```php
  do_action('wcaf_log_fraud_attempt', $email, $ip, 'custom_reason');
  ```

For full hooks, see `includes/wcaf-functions.php`.

### Integration with Other Plugins
- **Security Plugins**: Deactivate overlapping fraud checks (e.g., in iThemes Security).
- **Caching**: Purge cache after blacklist updates (WP Rocket, etc.).
- **Multisite**: Works per site; blacklists are site-specific.

## Best Practices

1. **Start Conservative**: Begin with strict blacklisting, then whitelist trusted patterns.
2. **Monitor Logs**: Review daily for false positives.
3. **Test Thoroughly**: Use staging site; simulate fraud with tools like WooCommerce test mode.
4. **Backup Data**: Export blacklists/logs before major updates.
5. **Legal Compliance**: Ensure blocking complies with privacy laws (GDPR, etc.); allow appeals.

### Handling False Positives
- Legitimate customer blocked? Remove from blacklist and whitelist their email.
- Check **WCAF > Fraud Attempt Logs** and delete erroneous entries.
- Contact via custom message directing to support.

## Scenarios and Examples

### Scenario 1: Blocking Repeat Card Declines
- Customer: john.doe@email.com, IP 203.0.113.5
- 3 failed credit card attempts → Auto-blacklist after 3rd.
- 4th attempt: Checkout blocked; log: "Auto-blacklisted after 3 failures."

### Scenario 2: Geographic Fraud
- Blacklist address: "%Nigeria%" (wildcard for high-risk area).
- Customer enters Nigerian address → Blocked at checkout.

### Scenario 3: VIP Whitelist
- Whitelist email: ceo@company.com, gateway: stripe.
- CEO uses Stripe → Bypasses all checks.

## Performance Considerations

- **HPOS Stores**: Optimal with WC 8.2+; uses efficient queries.
- **Large Blacklists**: For 1000+ entries, consider database optimization.
- **Blocks Checkout**: v2.9.0+ ensures <50ms validation on Store API.

For migration or installation issues, see [INSTALL.md](INSTALL.md). Report bugs at [GitHub Issues](https://github.com/tyfricko/wc-anti-fraud/issues).

---

&copy; 2025 Matej Zlatič. Licensed under GPLv2 or later.