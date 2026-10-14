<?php
/**
 * Plugin Name: CTCL Analytics
 * Plugin URI: https://github.com/ujw0l/ctcl-analytics
 * Description: Sales and order analytics for CT Commerce Lite with monthly reports and CSV export.
 * Version: 1.2.0
 * Author: Ujwol Bastakoti
 * Author URI: https://ujw0l.github.io/
 * Text Domain: ctcl-analytics
 * License: GPLv2 or later
 * Requires at least: 5.5.2
 * Requires PHP: 7.4.9
 */

namespace ctclAnalytics;

if (!defined('ABSPATH')) {
    exit;
}

class ctclAnalytics {
    const VERSION = '1.2.0';
    private $report = null;

    public function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'ctclAEnequeScript'));
        add_filter('ctcl-info-tab-sub-tab', array($this, 'ctclSubTabHtml'), 20, 1);
    }

    public function ctclSubTabHtml($tabs) {
        if (current_user_can('manage_options')) {
            $tabs[] = array('title' => __('Analytics', 'ctcl-analytics'), 'icon' => 'dashicons-chart-line', 'html' => $this->subTabHtml());
        }
        return $tabs;
    }

    public function ctclAEnequeScript($hook = '') {
        $tab = isset($_GET['tab']) ? $_GET['tab'] : 'info';
        if ($hook !== 'toplevel_page_ctclAdminPanel' || !current_user_can('manage_options') || $tab !== 'info') {
            return;
        }
        $url = plugin_dir_url(__FILE__);
        wp_enqueue_style('ctcla-admin', $url . 'css/analytics.css', array(), self::VERSION);
        wp_enqueue_script('ctcla-chart', $url . 'js/chart.js', array(), '4.4.4', true);
        wp_enqueue_script('ctcla-report', $url . 'js/report.js', array(), self::VERSION, true);
        wp_enqueue_script('ctcla-admin', $url . 'js/analytics.js', array('ctcla-chart', 'ctcla-report'), self::VERSION, true);
        wp_localize_script('ctcla-admin', 'ctclAnalyticsObject', array(
            'data' => $this->reportData(),
            'currency' => (string) get_option('ctcl_currency', ''),
            'locale' => str_replace('_', '-', get_locale()),
            'labels' => array(
                'sales' => __('Item sales', 'ctcl-analytics'),
                'orders' => __('Orders', 'ctcl-analytics'),
                'average' => __('Average order value', 'ctcl-analytics'),
                'month' => __('Month', 'ctcl-analytics'),
                'best' => __('Best month', 'ctcl-analytics'),
                'noOrders' => __('No orders in this period', 'ctcl-analytics'),
                'noBaseline' => __('No sales in the previous period', 'ctcl-analytics'),
                'comparison' => __('%s vs previous period', 'ctcl-analytics'),
                'period' => __('%1$s – %2$s · %3$s months', 'ctcl-analytics'),
                'partial' => __('Current month is partial', 'ctcl-analytics'),
                'chartUnavailable' => __('The chart is unavailable. You can still use the report table and CSV export.', 'ctcl-analytics'),
            ),
        ));
    }

    /** One query per request, with half-open month boundaries in the site timezone. */
    public function reportData() {
        if (!current_user_can('manage_options')) {
            return array('months' => array(), 'skipped' => 0, 'error' => false);
        }
        if ($this->report !== null) {
            return $this->report;
        }
        global $wpdb;
        $now = current_datetime();
        $first = $now->modify('first day of this month')->setTime(0, 0, 0)->modify('-23 months');
        $months = array();
        for ($i = 0; $i < 24; $i++) {
            $date = $first->modify('+' . $i . ' months');
            $key = $date->format('Y-m');
            $months[$key] = array(
                'key' => $key, 'label' => wp_date('F Y', $date->getTimestamp(), wp_timezone()),
                'pendingSales' => 0, 'pendingOrders' => 0, 'completeSales' => 0, 'completeOrders' => 0,
            );
        }
        $sql = $wpdb->prepare(
            "SELECT orderId, orderDetail, orderStatus FROM {$wpdb->prefix}ctclOrders WHERE orderId >= %d AND orderId < %d AND orderStatus IN ('pending', 'complete')",
            $first->getTimestamp(), $now->getTimestamp() + 1
        );
        $orders = $wpdb->get_results($sql, ARRAY_A);
        $skipped = 0;
        foreach ((array) $orders as $order) {
            $raw = isset($order['orderDetail']) ? $order['orderDetail'] : null;
            $detail = is_string($raw) ? json_decode($raw, true) : null;
            if (is_string($raw) && json_last_error() !== JSON_ERROR_NONE) {
                // Older CTC Lite stores may contain escaped JSON.
                $detail = json_decode(stripslashes($raw), true);
            }
            $total = is_array($detail) && isset($detail['items-total']) ? $detail['items-total'] : null;
            $status = isset($order['orderStatus']) ? $order['orderStatus'] : '';
            if (!isset($order['orderId']) || !ctype_digit((string) $order['orderId']) ||
                !in_array($status, array('pending', 'complete'), true) || !is_numeric($total) ||
                !is_finite((float) $total) || (float) $total < 0 || (float) $total > PHP_INT_MAX / 100) {
                $skipped++;
                continue;
            }
            $key = wp_date('Y-m', (int) $order['orderId'], wp_timezone());
            if (!isset($months[$key])) {
                continue;
            }
            // Integer minor units keep ordinary two-decimal item totals stable when summed.
            $months[$key][$status . 'Sales'] += (int) round((float) $total * 100);
            $months[$key][$status . 'Orders']++;
        }
        foreach ($months as &$month) {
            $month['pendingSales'] /= 100;
            $month['completeSales'] /= 100;
        }
        unset($month);
        $this->report = array('months' => array_values($months), 'skipped' => $skipped, 'error' => $orders === null);
        return $this->report;
    }

    /** Preserve the original public chart-data shape for integrations. */
    public function ctclaChartData() {
        $report = $this->reportData();
        $data = array();
        foreach (array_slice($report['months'], -12) as $month) {
            $data[] = array($month['label'], $month['pendingSales'] + $month['completeSales']);
        }
        return $data;
    }

    public function subTabHtml() {
        if (!current_user_can('manage_options')) {
            return '';
        }
        $report = $this->reportData();
        ob_start();
        ?>
        <section class="ctcl-analytics-tab-main" aria-labelledby="ctcla-heading">
            <div class="ctcla-heading"><div>
                <h2 id="ctcla-heading"><?php esc_html_e('Store Analytics', 'ctcl-analytics'); ?></h2>
                <p><?php esc_html_e('Track your store performance, month by month.', 'ctcl-analytics'); ?></p>
            </div><span class="ctcla-badge"><?php echo esc_html(get_option('ctcl_currency', '')); ?></span></div>
            <noscript><p class="notice notice-warning"><?php esc_html_e('Enable JavaScript to view interactive analytics and export reports.', 'ctcl-analytics'); ?></p></noscript>
            <?php if ($report['error']) : ?>
                <p class="notice notice-error"><?php esc_html_e('Order data could not be loaded. Check that CT Commerce Lite is activated and its orders table is available.', 'ctcl-analytics'); ?></p>
            </section>
            <?php return ob_get_clean(); endif; ?>
            <?php if ($report['skipped']) : ?>
                <p class="notice notice-warning"><?php echo esc_html(sprintf(__('Skipped %d orders with unreadable details or invalid item totals in the available 24-month history.', 'ctcl-analytics'), $report['skipped'])); ?></p>
            <?php endif; ?>
            <div class="ctcla-toolbar">
                <label for="ctcla-period"><?php esc_html_e('Period', 'ctcl-analytics'); ?>
                    <select id="ctcla-period"><option value="3"><?php esc_html_e('Last 3 months', 'ctcl-analytics'); ?></option><option value="6"><?php esc_html_e('Last 6 months', 'ctcl-analytics'); ?></option><option value="12" selected><?php esc_html_e('Last 12 months', 'ctcl-analytics'); ?></option></select>
                </label>
                <label for="ctcla-status"><?php esc_html_e('Order status', 'ctcl-analytics'); ?>
                    <select id="ctcla-status"><option value="all"><?php esc_html_e('Pending + completed', 'ctcl-analytics'); ?></option><option value="complete"><?php esc_html_e('Completed only', 'ctcl-analytics'); ?></option><option value="pending"><?php esc_html_e('Pending only', 'ctcl-analytics'); ?></option></select>
                </label>
                <button type="button" id="ctcla-export-csv-submit" class="button button-primary"><?php esc_html_e('Export CSV', 'ctcl-analytics'); ?></button>
            </div>
            <p id="ctcla-period-label" class="ctcla-period-label" aria-live="polite"></p>
            <div class="ctcla-metrics">
                <div class="ctcla-card"><span><?php esc_html_e('Item sales', 'ctcl-analytics'); ?></span><strong id="ctcla-sales">—</strong><small id="ctcla-growth"></small></div>
                <div class="ctcla-card"><span><?php esc_html_e('Orders', 'ctcl-analytics'); ?></span><strong id="ctcla-orders">—</strong><small><?php esc_html_e('In the selected period', 'ctcl-analytics'); ?></small></div>
                <div class="ctcla-card"><span><?php esc_html_e('Average order value', 'ctcl-analytics'); ?></span><strong id="ctcla-average">—</strong><small><?php esc_html_e('Item sales divided by orders', 'ctcl-analytics'); ?></small></div>
                <div class="ctcla-card"><span><?php esc_html_e('Best month', 'ctcl-analytics'); ?></span><strong id="ctcla-best">—</strong><small id="ctcla-best-sales"></small></div>
            </div>
            <div class="ctcla-chart-panel">
                <div class="ctcla-chart-heading"><h3><?php esc_html_e('Monthly performance', 'ctcl-analytics'); ?></h3><div>
                    <label for="ctcla-metric" class="screen-reader-text"><?php esc_html_e('Chart metric', 'ctcl-analytics'); ?></label><select id="ctcla-metric"><option value="sales"><?php esc_html_e('Item sales', 'ctcl-analytics'); ?></option><option value="orders"><?php esc_html_e('Orders', 'ctcl-analytics'); ?></option></select>
                    <label for="ctcla-chart-type" class="screen-reader-text"><?php esc_html_e('Chart style', 'ctcl-analytics'); ?></label><select id="ctcla-chart-type"><option value="line"><?php esc_html_e('Line chart', 'ctcl-analytics'); ?></option><option value="bar"><?php esc_html_e('Bar chart', 'ctcl-analytics'); ?></option></select>
                </div></div>
                <p id="ctcla-empty" hidden><?php esc_html_e('No orders match this period and status. Try another filter.', 'ctcl-analytics'); ?></p>
                <p id="ctcla-chart-error" hidden></p>
                <div class="ctclAChart"><canvas id="myChart" role="img" aria-label="<?php esc_attr_e('Monthly performance chart. Values are available in the monthly report table below.', 'ctcl-analytics'); ?>"></canvas></div>
            </div>
            <div class="ctcla-table-panel"><h3><?php esc_html_e('Monthly report', 'ctcl-analytics'); ?></h3><div class="ctcla-table-scroll">
                <table class="widefat striped"><caption class="screen-reader-text"><?php esc_html_e('Monthly item sales, orders, and average order value for the selected filters.', 'ctcl-analytics'); ?></caption><thead><tr><th scope="col"><?php esc_html_e('Month', 'ctcl-analytics'); ?></th><th scope="col"><?php esc_html_e('Item sales', 'ctcl-analytics'); ?></th><th scope="col"><?php esc_html_e('Orders', 'ctcl-analytics'); ?></th><th scope="col"><?php esc_html_e('Average order value', 'ctcl-analytics'); ?></th></tr></thead><tbody id="ctcla-table-body"></tbody><tfoot id="ctcla-table-footer"></tfoot></table>
            </div></div>
            <p class="ctcla-note"><?php esc_html_e('Sales use stored item totals before tax, shipping, and order-level discounts. Refunded and other order statuses are excluded. The current month is partial; comparisons use the preceding equal-length period, including full months. Amounts are rounded to two decimal places.', 'ctcl-analytics'); ?></p>
        </section>
        <?php
        return ob_get_clean();
    }
}

// Wait for all plugins so alphabetical activation order cannot hide the dependency.
add_action('plugins_loaded', function () {
    if (class_exists('ctcLite')) {
        new ctclAnalytics();
        return;
    }
    add_action('admin_notices', function () {
        if (current_user_can('activate_plugins')) {
            echo '<div class="notice notice-error"><p>';
            esc_html_e('CTCL Analytics requires CT Commerce Lite to be installed and activated.', 'ctcl-analytics');
            echo ' <a href="' . esc_url(admin_url('plugin-install.php?s=ctc-lite&tab=search&type=term')) . '">' . esc_html__('Find CT Commerce Lite', 'ctcl-analytics') . '</a></p></div>';
        }
    });
}, 20);
