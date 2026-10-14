const test = require('node:test');
const assert = require('node:assert/strict');
const { selectReport, toCSV } = require('../js/report.js');
const months = Array.from({ length: 24 }, (_, i) => ({ key: 'month-' + i, label: 'Month ' + i, pendingSales: i + 1, pendingOrders: 1, completeSales: (i + 1) * 2, completeOrders: 2 }));
test('period and status filters produce weighted average and previous-period comparison', () => {
    const report = selectReport(months, 3, 'complete');
    assert.equal(report.rows.length, 3);
    assert.equal(report.sales, 138);
    assert.equal(report.orders, 6);
    assert.equal(report.average, 23);
    assert.equal(report.best.key, 'month-23');
    assert.equal(report.growth, 15);
    assert.equal(selectReport(months, 6, 'pending').sales, 129);
    assert.equal(selectReport(months, 12, 'all').orders, 36);
});
test('empty data and zero baseline stay finite', () => {
    const empty = months.map(row => ({ ...row, pendingSales: 0, completeSales: 0, pendingOrders: 0, completeOrders: 0 }));
    const report = selectReport(empty, 12, 'all');
    assert.equal(report.average, 0);
    assert.equal(report.growth, null);
    assert.equal(report.best, null);
    assert.deepEqual(selectReport([], 12, 'all').rows, []);
});
test('summation avoids ordinary floating-point artefacts', () => {
    const report = selectReport(months.map(row => ({ ...row, pendingSales: 0.1, completeSales: 0.2 })), 3, 'all');
    assert.equal(report.sales, 0.9);
});
test('CSV uses UTF-8 BOM, CRLF, standard quote escaping, and formula protection', () => {
    const report = selectReport(months, 3, 'all');
    report.rows[0].label = 'March, "2026"';
    report.rows[1].label = '=HYPERLINK("unsafe")';
    const csv = toCSV(report, ['Month', 'Sales (=USD)', 'Orders', 'Average']);
    assert.ok(csv.startsWith('\uFEFF"Month"'));
    assert.ok(csv.includes('"March, ""2026"""'));
    assert.ok(csv.includes('"\'=HYPERLINK(""unsafe"")"'));
    assert.equal(csv.split('\r\n').length, 5);
});
test('invalid filters fall back to the default report', () => {
    const report = selectReport(months, -1, 'refund');
    assert.equal(report.count, 12);
    assert.equal(report.status, 'all');
});

test('a free-order month is selected ahead of empty months when all sales are zero', () => {
    const data = months.map(row => ({ ...row, pendingSales: 0, completeSales: 0, pendingOrders: 0, completeOrders: 0 }));
    data[23].completeOrders = 1;
    const report = selectReport(data, 3, 'all');
    assert.equal(report.best.key, 'month-23');
    assert.equal(report.orders, 1);
});
