<?php
// Run with: php tests/analytics.php. No WordPress installation required.
define('ABSPATH', __DIR__);
define('ARRAY_A', 'ARRAY_A');
$allowed = true;
$hooks = array();
function add_action($name, $callback, $priority = 10) { global $hooks; $hooks[$name][] = $callback; }
function add_filter($name, $callback, $priority = 10, $args = 1) {}
function current_user_can($cap) { global $allowed; return $allowed; }
function current_datetime() { return new DateTimeImmutable('2026-03-01 12:00:00', wp_timezone()); }
function wp_timezone() { return new DateTimeZone('Asia/Kathmandu'); }
function wp_date($format, $timestamp, $timezone) { return (new DateTimeImmutable('@' . $timestamp))->setTimezone($timezone)->format($format); }
function __( $text, $domain = '' ) { return $text; }
function esc_html($text) { return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8'); }
function esc_html_e($text, $domain = '') { echo esc_html($text); }
function plugin_dir_url($file) { return '/plugin/'; }
function get_option($name, $default = '') { return $default; }
function get_locale() { return 'en_US'; }
$assets = array();
function wp_enqueue_style(...$args) { global $assets; $assets[] = $args; }
function wp_enqueue_script(...$args) { global $assets; $assets[] = $args; }
function wp_localize_script(...$args) {}
class ctcLite {}
class FakeDB {
    public $prefix = 'wp_';
    public $rows = array();
    public $queries = 0;
    public $bounds;
    public function prepare($sql, ...$bounds) { $this->bounds = $bounds; return $sql; }
    public function get_results($sql, $type) {
        $this->queries++;
        if ($this->rows === null) return null;
        return array_values(array_filter($this->rows, function($row) {
            return $row['orderId'] >= $this->bounds[0] && $row['orderId'] < $this->bounds[1] && in_array($row['orderStatus'], array('pending', 'complete'), true);
        }));
    }
}
function check($value, $message) { if (!$value) throw new RuntimeException($message); echo "PASS: $message\n"; }
function order($date, $total, $status = 'complete', $escaped = false) {
    $raw = json_encode(array('items-total' => $total));
    return array('orderId' => (new DateTimeImmutable($date, wp_timezone()))->getTimestamp(), 'orderDetail' => $escaped ? addslashes($raw) : $raw, 'orderStatus' => $status);
}
require dirname(__DIR__) . '/ctcl-analytics.php';
check(count($hooks['plugins_loaded']) === 1, 'Dependency detection waits until plugins_loaded');
$hooks['plugins_loaded'][0]();
$wpdb = new FakeDB();
$wpdb->rows = array(
    order('2024-04-01 00:00:00', 7),
    order('2024-03-31 23:59:59', 99),
    order('2026-02-28 23:59:59', '12.34'),
    order('2026-03-01 00:00:00', 0.1, 'pending'),
    order('2026-03-01 00:00:01', 0.2, 'pending', true),
    order('2026-03-01 00:00:02', 0, 'complete'),
    order('2026-03-01 12:00:01', 90),
    order('2026-03-01 00:00:03', 80, 'refund'),
    order('2026-03-01 00:00:04', 'broken'),
    order('2026-03-01 00:00:05', -10),
    order('2026-03-01 00:00:07', '1e300'),
    array('orderId' => (new DateTimeImmutable('2026-03-01 00:00:06', wp_timezone()))->getTimestamp(), 'orderDetail' => '{invalid', 'orderStatus' => 'complete'),
);
$plugin = new ctclAnalytics\ctclAnalytics();
$data = $plugin->reportData();
check(count($data['months']) === 24 && $data['months'][0]['key'] === '2024-04', '24 chronological months are available for comparisons');
check($data['months'][22]['completeSales'] === 12.34, 'Last-day late-night orders are included in the site timezone');
check($data['months'][23]['pendingSales'] === 0.3 && $data['months'][23]['pendingOrders'] === 2, 'Escaped JSON and decimal totals aggregate correctly');
check($data['months'][23]['completeOrders'] === 1, 'Zero-value orders count; future and refunded orders do not');
check($data['skipped'] === 4, 'Malformed and negative totals are reported and skipped');
$plugin->ctclaChartData();
$plugin->reportData();
check($wpdb->queries === 1, 'Report data is reused within a request');
$_GET['tab'] = 'info';
$plugin->ctclAEnequeScript('dashboard');
check(count($assets) === 0, 'Other admin pages do not load analytics assets');
$plugin->ctclAEnequeScript('toplevel_page_ctclAdminPanel');
check(count($assets) === 4, 'The Information screen loads versioned analytics assets');
unset($_GET['tab']);
$plugin->ctclAEnequeScript('toplevel_page_ctclAdminPanel');
check(count($assets) === 8, 'The default Information screen also loads analytics assets');
$allowed = false;
check($plugin->reportData()['months'] === array() && $plugin->subTabHtml() === '' && $plugin->ctclSubTabHtml(array()) === array(), 'Unauthorized users cannot access cached data or markup');
$allowed = true;
$wpdb->rows = null;
check((new ctclAnalytics\ctclAnalytics())->reportData()['error'] === true, 'Database failures are distinguishable from empty reports');

$errorHtml = (new ctclAnalytics\ctclAnalytics())->subTabHtml();
check(substr_count($errorHtml, '<section') === 1 && substr_count($errorHtml, '</section>') === 1, 'Database-error markup leaves the admin layout intact');
