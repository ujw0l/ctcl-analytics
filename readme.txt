=== CTCL Analytics ===
Contributors: ujw0l
Donate link: https://www.patreon.com/ujw0l/membership
Tags: ecommerce, ctc-lite, analytics, sales reports, charts
Requires at least: 5.5.2
Tested up to: 7.1
Stable tag: 1.2.0
Requires PHP: 7.4.9
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Sales and order analytics for CT Commerce Lite, with period filters, monthly charts, summary cards, and CSV reports.

== Description ==

CTCL Analytics adds an Analytics subtab to CT Commerce Lite's Information screen. Explore your store's item sales and order volume without sending order data to an external analytics service.

= Features =

* View the last 3, 6, or 12 calendar months, including the current month.
* Filter pending orders, completed orders, or both.
* See item sales, order counts, average order value, and the best sales month.
* Compare item sales with the preceding period of the same length.
* Switch between sales and order charts, using line or bar views.
* Read a monthly report table with totals and weighted average order value.
* Export the selected period and status to a UTF-8 CSV file.
* Use a responsive layout with labeled controls and a report table accessible to screen readers.
* Use month boundaries in your WordPress site's timezone, including orders late on the final day of a month.
* Receive a warning when unreadable order details or invalid item totals are skipped.

= What the report measures =

Item sales use the stored `items-total` field from CT Commerce Lite orders, before tax, shipping, and order-level discounts. Average order value is item sales divided by the number of included orders. Free orders count toward the order total. Amounts are rounded to two decimal places.

Refunded orders and statuses other than pending or complete are excluded. Pending orders are not proof of collected payment; choose Completed only when you want completed-order reporting. These reports do not calculate net revenue, profit, or payment settlement.

The current month is partial. Comparisons use the immediately preceding equal-length period of calendar months; a partial current month may be compared with a full previous month. A comparison is unavailable when the previous period has no item sales.

The plugin reads up to 24 months of order history to support a 12-month comparison. It performs one orders query per analytics request and reuses the results for the chart, cards, and table. Changing filters and exporting CSV happen in your browser.

= Privacy and bundled libraries =

Analytics are available only to administrators with the manage_options capability. Only monthly totals and counts are sent to the dashboard, not customer names, addresses, or individual order details.

Chart.js 4.4.4 is bundled locally under the MIT license. No CDN connection or third-party analytics account is required. Chart.js source: https://github.com/chartjs/Chart.js/tree/v4.4.4

== Installation ==

1. Install and activate CT Commerce Lite.
2. Upload the ctcl-analytics folder to /wp-content/plugins/, or install its ZIP through Plugins > Add New Plugin > Upload Plugin.
3. Activate CTCL Analytics through Plugins.
4. Open CT Commerce > Information > Analytics.
5. Choose a period and order status. Use Export CSV to download the selected report.

== Frequently Asked Questions ==

= Does this work without CT Commerce Lite? =

No. CTCL Analytics reads CT Commerce Lite's orders table and adds a subtab to its administration screen. If the parent plugin is missing, an administrator notice includes a link to find it.

= Why do totals differ from payment or checkout totals? =

Reports use item totals before shipping, tax, and order-level discounts. Pending orders are included by default, and refunded orders are excluded. Select Completed only to limit the report to completed orders.

= Does CSV export follow my filters? =

Yes. It contains the selected months and order status, with item sales, order counts, and average order value. The filename includes the period and status. UTF-8 encoding preserves localized month names, and standard CSV quoting handles commas and quotation marks.

= Can I view months with no orders? =

Yes. The chart and report table retain zero-value months. If the whole selected period has no orders, the dashboard displays an empty-state message.

= Which WordPress version has been tested? =

Version 1.2.0 was tested with WordPress 7.1.3, PHP 8.3, and CT Commerce Lite 2.8.3 in a local installation. Automated regression checks cover month boundaries, permissions, order parsing, calculations, and CSV output.

== Screenshots ==

1. Twelve-month item-sales overview with summary cards, comparison, line chart, and monthly report. Uses sample orders.
2. Three-month completed-order report with an order-volume bar chart and matching summary cards. Uses sample orders.

== Changelog ==

= 1.2.0 =
* Added 3-, 6-, and 12-month reporting, order-status filters, and sales/order chart controls.
* Added summary cards, previous-period comparison, and a monthly report table.
* Improved CSV export with selected filters, descriptive filenames, UTF-8, standard escaping, and spreadsheet-formula protection.
* Fixed month-end order omissions and aligned month boundaries with the site timezone.
* Excluded refunded orders and handled malformed or escaped legacy order details.
* Limited analytics data and assets to the authorized store Information screen.
* Deferred dependency detection until all plugins are loaded.
* Added responsive styles, WordPress.org screenshots, and regression tests.
* Tested with WordPress 7.1.3.

= 1.1.0 =
* Bundled Chart.js locally.

= 1.0.0 =
* First stable version with a twelve-month sales chart and CSV export.

== Upgrade Notice ==

= 1.2.0 =
Adds filtered sales and order reporting. Refunded orders are now excluded, and month-end orders are counted correctly, so totals may differ from earlier versions.
