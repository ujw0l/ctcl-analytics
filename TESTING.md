# Validation

Run the dependency-free regression checks:

```sh
php -l ctcl-analytics.php
php tests/analytics.php
node --check js/analytics.js
node --test tests/report.test.cjs
```

Release 1.2.0 was also checked in a clean local WordPress 7.1.3 installation with PHP 8.3.4, CT Commerce Lite 2.8.3, and the official SQLite Database Integration drop-in. Sample orders covered 24 calendar months. A MySQL server was not available for integration testing; the query uses standard comparison operators and prepared numeric values.

Browser checks:

- Both plugins activate, and the Analytics subtab renders on the Information screen, including its default URL with no `tab` parameter.
- The 12-month overview shows 336 sample orders and 42,378.00 USD in item sales.
- Filtering to the last three months and completed orders shows 90 orders and 12,634.50 USD. Its CSV has three rows matching the monthly table.
- Six-month pending-only filtering shows 18 orders and 2,064.00 USD; sales/order metrics and line/bar charts update without a page reload.
- The 1440px layout has four summary columns; the 390px layout has one column and no document overflow.
- Empty reports display zero totals and a helpful message; absent Chart.js leaves the table and export available.
- No browser console errors occur in the analytics screen.

The PHP regression checks cover late-night month-end orders in Asia/Kathmandu, the first included month, escaped JSON, invalid/negative totals, zero-value orders, future orders, refunds, query reuse, restricted asset loading, permission checks, and database errors. JavaScript checks cover weighted averages, comparison periods, zero baselines, decimal addition, CSV quoting, formula protection, and fallback filters.

# WordPress.org assets and packaging

`.wordpress-org/screenshot-1.jpg` and `screenshot-2.jpg` are real captures of the local WordPress admin using sample orders. Their numbered captions are in `readme.txt`. Copy these images to the WordPress.org SVN repository's top-level `assets/` directory when publishing there. A GitHub tag does not update the WordPress.org listing.

A plugin ZIP should contain `ctcl-analytics.php`, `readme.txt`, `LICENSE`, `css/`, and `js/` under a single `ctcl-analytics/` folder. Exclude `.git/`, `.wordpress-org/`, `tests/`, and this developer document.
