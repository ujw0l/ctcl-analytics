/* Pure report calculations shared by the dashboard and regression tests. */
(function (root) {
    'use strict';
    const round = value => Math.round((value + Number.EPSILON) * 100) / 100;
    function selectReport(data, period, status) {
        const count = [3, 6, 12].includes(Number(period)) ? Number(period) : 12;
        const filter = ['pending', 'complete'].includes(status) ? status : 'all';
        const months = data.map(month => {
            const sales = round(filter === 'all' ? month.pendingSales + month.completeSales : month[filter + 'Sales']);
            const orders = filter === 'all' ? month.pendingOrders + month.completeOrders : month[filter + 'Orders'];
            return { key: month.key, label: month.label, sales, orders, average: orders ? round(sales / orders) : 0 };
        });
        const rows = months.slice(-count);
        const previous = months.slice(-count * 2, -count);
        const sales = round(rows.reduce((sum, month) => sum + month.sales, 0));
        const orders = rows.reduce((sum, month) => sum + month.orders, 0);
        const baseline = round(previous.reduce((sum, month) => sum + month.sales, 0));
        return {
            rows, sales, orders, average: orders ? round(sales / orders) : 0,
            growth: baseline ? ((sales - baseline) / baseline) * 100 : null,
            best: orders ? rows.filter(month => month.orders > 0).reduce((best, month) => !best || month.sales > best.sales ? month : best, null) : null,
            count, status: filter,
        };
    }
    function csvCell(value) {
        let text = String(value);
        // Neutralize spreadsheet formulas in localized labels and custom currency settings.
        if (/^\s*[=+\-@]/.test(text)) text = "'" + text;
        return '"' + text.replace(/"/g, '""') + '"';
    }
    function toCSV(report, headers) {
        const rows = [headers, ...report.rows.map(row => [row.label, row.sales.toFixed(2), row.orders, row.average.toFixed(2)])];
        return '\uFEFF' + rows.map(row => row.map(csvCell).join(',')).join('\r\n') + '\r\n';
    }
    const api = { selectReport, toCSV };
    if (typeof module !== 'undefined' && module.exports) module.exports = api;
    else root.ctclaReport = api;
})(typeof window !== 'undefined' ? window : globalThis);
