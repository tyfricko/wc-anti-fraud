# Contributing to WC Anti Fraud

Thank you for your interest in contributing to WC Anti Fraud! This plugin is a rebranded and actively maintained fork of the original [Woo Manage Fraud Orders](https://wordpress.org/plugins/woo-manage-fraud-orders/) plugin. All contributions should focus on enhancing compatibility with modern WordPress/WooCommerce (from version 2.7.0 onward), improving security, performance, and user experience.

We welcome contributions that align with the plugin's core mission: fraud prevention for WooCommerce stores with full HPOS and Blocks Checkout support.

## How to Contribute

### 1. Report Bugs or Request Features
- **GitHub Issues**: Use the [Issues page](https://github.com/tyfricko/wc-anti-fraud/issues) to report bugs, suggest improvements, or request new features.
  - For bugs: Include WordPress/WooCommerce versions, PHP version, steps to reproduce, and error logs (enable `WP_DEBUG`).
  - For features: Describe the use case and how it fits with existing functionality (e.g., new blacklist types).
- **Support Questions**: For usage help, check [USAGE.md](USAGE.md), [INSTALL.md](INSTALL.md), or the [WordPress.org forums](https://wordpress.org/support/plugin/wc-anti-fraud/) (once submitted).

### 2. Submit Pull Requests
Before submitting:
- Fork the repository and create a feature branch: `git checkout -b feature/your-feature-name`.
- Ensure your changes address a specific issue or enhancement.
- Test thoroughly (see Development Setup below).

**PR Guidelines**:
- **Branch Naming**: Use `feature/`, `fix/`, or `docs/` prefixes.
- **Commit Messages**: Clear and descriptive, e.g., "Fix: Resolve IP range blacklisting bug in v2.9.1".
- **Code Style**: Follow WordPress Coding Standards (PHP, JS). Use tools like PHPCS.
- **Documentation**: Update README.md, USAGE.md, or readme.txt as needed.
- **Changelog**: Add entries to CHANGELOG.md for user-facing changes.
- **Version Bumps**: Only for releases; tag as `vX.Y.Z`.

We review PRs within 1-2 weeks. Larger changes may require discussion first.

### 3. Development Setup

To contribute code:

1. **Clone the Repository**:
   ```
   git clone https://github.com/tyfricko/wc-anti-fraud.git
   cd wc-anti-fraud
   ```

2. **Local Environment**:
   - WordPress 6.6+ with WooCommerce 10.1+ (HPOS enabled).
   - PHP 7.4+ (test up to 8.2).
   - Use LocalWP, XAMPP, or Docker for setup.
   - Enable debug: Add to `wp-config.php`:
     ```
     define('WP_DEBUG', true);
     define('WP_DEBUG_LOG', true);
     ```

3. **Install Dependencies** (if any):
   - No Composer dependencies currently, but run `composer install` if added.
   - For testing: See tests/ folder; requires PHPUnit.

4. **Plugin Activation**:
   - Copy to `/wp-content/plugins/wc-anti-fraud/`.
   - Activate in WordPress admin.
   - Configure via **WooCommerce > Settings > Anti Fraud**.

5. **Code Structure** (MVC Pattern since v2.8.0):
   - **Controllers**: `includes/controllers/` – Handle WooCommerce hooks and actions.
   - **Models**: `includes/models/` – Database operations (blacklists, logs, migration).
   - **Admin**: `includes/admin/` – Settings pages, metaboxes, tables.
   - **Core**: `includes/` – Main classes, functions (`wcaf-functions.php`).
   - **Main File**: `woo-manage-fraud-orders.php` – Plugin header and loader.

6. **Running Tests**:
   - Basic tests in `tests/` (HPOS compatibility, blacklist validation).
   - Install WP-CLI: `wp plugin activate wc-anti-fraud`.
   - Run: `wp scaffold plugin-tests wc-anti-fraud` (setup), then `phpunit`.
   - Manual testing: Use test files like `test-blacklist-functionality.php`.

7. **Code Linting**:
   - Install PHPCS: `composer require --dev wp-coding-standards/wpcs`.
   - Run: `phpcs --standard=WordPress includes/`.
   - Fix issues with PHPCBF: `phpcbf --standard=WordPress includes/`.

### 4. Development Guidelines

- **Security First**: Always sanitize inputs (`sanitize_email()`, `esc_attr()`). Use nonces for forms.
- **HPOS Compatibility**: Use `wc_get_order()` and meta APIs; avoid direct DB queries for orders.
- **Performance**: Limit queries; cache blacklists if >1000 entries.
- **Internationalization**: Use `__()` and `_e()` for translatable strings. Text domain: `wc-anti-fraud`.
- **Hooks & Filters**: Extend via WordPress actions/filters, not direct edits. See USAGE.md for examples.
- **Backward Compatibility**: Support WooCommerce 8.2+; deprecate old features gracefully.
- **No External Dependencies**: Keep lightweight; no required libraries.

**Fork-Specific Notes**:
- Changes should build on v2.7.0+ architecture (MVC refactor, HPOS handlers).
- Reference original plugin for legacy behavior but prioritize modern standards.
- Avoid reintroducing outdated code (e.g., pre-PHP 7.4 syntax).

### 5. Testing Requirements

All PRs must include:
- Unit tests for new features (PHPUnit in `tests/`).
- Manual verification on:
  - Classic Checkout (WC <10).
  - Blocks Checkout (WC 10+ Store API).
  - HPOS enabled/disabled.
  - PHP 7.4, 8.0, 8.2.
- Screenshots or videos for UI changes.

### 6. Code Review Process

1. PR submitted → Automated checks (if CI set up).
2. Review for standards, security, and functionality.
3. Feedback provided; iterate as needed.
4. Merge to `main` → Tagged release if major.

### 7. Community Guidelines

- Be respectful and inclusive.
- Credit original contributors where applicable.
- For translations: Submit to [languages/wc-anti-fraud.pot](languages/wc-anti-fraud.pot); use GlotPress once available.

## License

Contributions are licensed under GPLv2 or later, same as the plugin. By contributing, you agree that your submissions are original and comply with the license.

## Get Help

- Questions? Open an issue labeled "question".
- Join discussions on the [WordPress.org forum](https://wordpress.org/support/plugin/wc-anti-fraud/).
- Author: [Matej Zlatič](https://matejzlatic.com)

We appreciate your help in making WC Anti Fraud more robust for WooCommerce merchants!

---

&copy; 2025 Matej Zlatič. Based on original Woo Manage Fraud Orders.