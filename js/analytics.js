document.addEventListener('DOMContentLoaded', () => {
    'use strict';
    const dashboard = document.querySelector('.ctcl-analytics-tab-main');
    if (!dashboard || !window.ctclAnalyticsObject || !window.ctclaReport || !dashboard.querySelector('#ctcla-period')) return;
    const config = window.ctclAnalyticsObject;
    const get = id => dashboard.querySelector('#ctcla-' + id);
    let money, integer;
    try {
        money = new Intl.NumberFormat(config.locale, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        integer = new Intl.NumberFormat(config.locale, { maximumFractionDigits: 0 });
    } catch (error) {
        money = new Intl.NumberFormat('en', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        integer = new Intl.NumberFormat('en', { maximumFractionDigits: 0 });
    }
    const amount = value => money.format(value) + (config.currency ? ' ' + config.currency : '');
    let report, chart;
    function tableRow(values, total = false) {
        const row = document.createElement('tr');
        values.forEach((value, index) => {
            const cell = document.createElement(index === 0 ? 'th' : 'td');
            if (index === 0) cell.scope = 'row';
            cell.textContent = value;
            row.appendChild(cell);
        });
        if (total) row.className = 'ctcla-total-row';
        return row;
    }
    function renderChart() {
        const metric = get('metric').value;
        const type = get('chart-type').value;
        const orders = metric === 'orders';
        if (chart) chart.destroy();
        if (typeof window.Chart !== 'function') {
            get('chart-error').textContent = config.labels.chartUnavailable;
            get('chart-error').hidden = false;
            return;
        }
        chart = new window.Chart(dashboard.querySelector('#myChart'), {
            type,
            data: {
                labels: report.rows.map(row => row.label),
                datasets: [{
                    label: config.labels[metric] + (!orders && config.currency ? ' (' + config.currency + ')' : ''),
                    data: report.rows.map(row => row[metric]),
                    borderColor: '#2271b1', backgroundColor: type === 'bar' ? '#2271b1' : 'rgba(34,113,177,0.10)',
                    borderWidth: 2, pointRadius: 3, fill: type === 'line', tension: 0.2,
                }],
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: item => orders ? integer.format(item.parsed.y) : amount(item.parsed.y) } } },
                scales: { y: { beginAtZero: true, ticks: orders ? { precision: 0 } : {} }, x: { grid: { display: false } } },
            },
        });
    }
    function render() {
        report = window.ctclaReport.selectReport(config.data.months, get('period').value, get('status').value);
        if (!report.rows.length) return;
        get('sales').textContent = amount(report.sales);
        get('orders').textContent = integer.format(report.orders);
        get('average').textContent = amount(report.average);
        get('best').textContent = report.best ? report.best.label : '—';
        get('best-sales').textContent = report.best ? amount(report.best.sales) : config.labels.noOrders;
        get('growth').textContent = report.growth === null ? config.labels.noBaseline : config.labels.comparison.replace('%s', (report.growth > 0 ? '+' : '') + money.format(report.growth) + '%');
        get('growth').className = report.growth > 0 ? 'ctcla-positive' : report.growth < 0 ? 'ctcla-negative' : '';
        get('period-label').textContent = config.labels.period.replace('%1$s', report.rows[0].label).replace('%2$s', report.rows[report.rows.length - 1].label).replace('%3$s', report.count) + ' · ' + config.labels.partial;
        get('empty').hidden = report.orders !== 0;
        const rows = document.createDocumentFragment();
        report.rows.forEach(row => rows.appendChild(tableRow([row.label, amount(row.sales), integer.format(row.orders), amount(row.average)])));
        get('table-body').replaceChildren(rows);
        get('table-footer').replaceChildren(tableRow([config.labels.sales, amount(report.sales), integer.format(report.orders), amount(report.average)], true));
        renderChart();
    }
    ['period', 'status'].forEach(id => get(id).addEventListener('change', render));
    ['metric', 'chart-type'].forEach(id => get(id).addEventListener('change', renderChart));
    get('export-csv-submit').addEventListener('click', () => {
        const headers = [config.labels.month, config.labels.sales + (config.currency ? ' (' + config.currency + ')' : ''), config.labels.orders, config.labels.average + (config.currency ? ' (' + config.currency + ')' : '')];
        const csv = window.ctclaReport.toCSV(report, headers);
        const url = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8;' }));
        const link = document.createElement('a');
        link.href = url;
        link.download = 'CTCL-Sales-' + report.rows[0].key + '-to-' + report.rows[report.rows.length - 1].key + '-' + report.status + '.csv';
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.setTimeout(() => URL.revokeObjectURL(url), 1000);
    });
    render();
});
