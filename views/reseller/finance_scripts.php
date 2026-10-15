<script id="scripts">
(function() {
    let timelineChart = null;
    let gatewaysChart = null;
    let currentCurrency = '<?= htmlspecialchars($currency ?? ''); ?>';
    let currentRange = 'all';
    let currentDays = 30;

    // Resilient ApexCharts loader guard with queueing
    let apexLoading = false;
    let apexCallbacks = [];
    function ensureApexCharts(callback) {
        if (typeof ApexCharts !== 'undefined') {
            callback();
            return;
        }
        apexCallbacks.push(callback);
        if (apexLoading) return;
        apexLoading = true;

        var s = document.createElement('script');
        s.src = 'assets/vendor/libs/apex-charts/apexcharts.js';
        s.onload = function() {
            apexLoading = false;
            while (apexCallbacks.length) {
                try { (apexCallbacks.shift())(); } catch (e) { console.error(e); }
            }
        };
        s.onerror = function() {
            var cdn = document.createElement('script');
            cdn.src = 'https://cdn.jsdelivr.net/npm/apexcharts';
            cdn.onload = function() {
                apexLoading = false;
                while (apexCallbacks.length) {
                    try { (apexCallbacks.shift())(); } catch (e) { console.error(e); }
                }
            };
            cdn.onerror = function() {
                apexLoading = false;
                console.warn('Could not load ApexCharts.');
                $('#financeChartSpinner').addClass('d-none');
            };
            document.head.appendChild(cdn);
        };
        document.head.appendChild(s);
    }

    $(function() {
        // 1. DataTables Init
        if ($.fn.dataTable) {
            $.fn.dataTable.ext.errMode = 'none';
        }

        let ledgerTable = null;
        if ($.fn.DataTable && $('#financeLedgerTable').length) {
            ledgerTable = $('#financeLedgerTable').DataTable({
                responsive: true,
                order: [[2, 'desc']], // Sorted by Cash Paid desc
                columnDefs: [
                    { targets: 'no-sort', orderable: false, searchable: false }
                ],
                pageLength: 25,
                language: {
                    emptyTable: '<div class="text-center py-4 text-muted"><i class="ti tabler-inbox fs-1 d-block mb-2"></i>No sub-reseller records found</div>'
                },
                layout: {
                    topStart: {
                        pageLength: {},
                        buttons: [
                            { extend: 'copy', className: 'btn btn-sm btn-label-secondary me-1', text: '<i class="ti tabler-copy me-1"></i>Copy' },
                            { extend: 'csv', className: 'btn btn-sm btn-label-primary me-1', text: '<i class="ti tabler-file-spreadsheet me-1"></i>CSV' },
                            { extend: 'excel', className: 'btn btn-sm btn-label-success me-1', text: '<i class="ti tabler-file-analytics me-1"></i>Excel' },
                            { extend: 'print', className: 'btn btn-sm btn-label-info', text: '<i class="ti tabler-printer me-1"></i>Print' }
                        ]
                    },
                    topEnd: 'search'
                }
            });

            $('#ledgerSearchInput').on('keyup', function() {
                ledgerTable.search(this.value).draw();
            });
        }

        // 2. Render Timeline Chart from data object
        function renderTimelineChart(d) {
            $('#financeChartSpinner').addClass('d-none');
            const chartEl = document.querySelector('#financeTimelineChart');
            if (!chartEl) return;
            chartEl.innerHTML = '';

            const options = {
                chart: {
                    type: 'line',
                    height: 330,
                    toolbar: { show: false },
                    zoom: { enabled: false },
                    animations: { enabled: true, easing: 'easeinout', speed: 600 }
                },
                series: [
                    {
                        name: 'Sales Revenue',
                        type: 'area',
                        data: d.series_revenue || []
                    },
                    {
                        name: 'Points Sold to Subs',
                        type: 'column',
                        data: d.series_points_issued || []
                    },
                    {
                        name: 'Points Consumed',
                        type: 'line',
                        data: d.series_points_consumed || []
                    }
                ],
                stroke: {
                    curve: 'smooth',
                    width: [3, 0, 3]
                },
                fill: {
                    type: ['gradient', 'solid', 'solid'],
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.45,
                        opacityTo: 0.05,
                        stops: [0, 90, 100]
                    }
                },
                colors: ['#28c76f', '#7367f0', '#ea5455'],
                xaxis: {
                    categories: d.categories || [],
                    labels: {
                        style: { colors: '#a1acb8', fontSize: '11px' }
                    },
                    axisBorder: { show: false },
                    axisTicks: { show: false }
                },
                yaxis: [
                    {
                        title: { text: 'Sales Revenue', style: { color: '#28c76f', fontWeight: 500 } },
                        labels: {
                            formatter: val => val ? parseFloat(val).toFixed(0) : '0',
                            style: { colors: '#a1acb8' }
                        }
                    },
                    {
                        opposite: true,
                        title: { text: 'Points', style: { color: '#7367f0', fontWeight: 500 } },
                        labels: {
                            formatter: val => val ? parseFloat(val).toFixed(0) : '0',
                            style: { colors: '#a1acb8' }
                        }
                    }
                ],
                tooltip: {
                    shared: true,
                    intersect: false,
                    y: {
                        formatter: function(y, { seriesIndex }) {
                            if (typeof y === 'undefined') return '';
                            if (seriesIndex === 0) return '$' + parseFloat(y).toFixed(2);
                            return parseFloat(y).toFixed(2) + ' pts';
                        }
                    }
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'right',
                    fontSize: '12px'
                },
                grid: {
                    borderColor: '#f1f1f1',
                    strokeDashArray: 4
                }
            };

            ensureApexCharts(function() {
                if (timelineChart) {
                    timelineChart.destroy();
                }
                chartEl.innerHTML = '';
                timelineChart = new ApexCharts(chartEl, options);
                timelineChart.render();
            });
        }

        // Fetch and render Timeline Chart via AJAX
        function loadTimelineChart(days, currency) {
            $('#financeChartSpinner').removeClass('d-none');
            let url = 'api?action=billing_finance_chart&days=' + encodeURIComponent(days);
            if (currency) {
                url += '&currency=' + encodeURIComponent(currency);
            }

            fetch(url)
                .then(r => r.json())
                .then(res => {
                    if (res.status === 'success' && res.data) {
                        renderTimelineChart(res.data);
                    }
                })
                .catch(err => console.error('Failed to load finance chart:', err))
                .finally(() => {
                    $('#financeChartSpinner').addClass('d-none');
                });
        }

        // 3. Render Gateways Donut Chart
        function renderGatewaysChart(gateways) {
            const el = document.querySelector('#gatewaysDonutChart');
            if (!el) return;
            el.innerHTML = '';

            if (!gateways || gateways.length === 0) {
                el.innerHTML = '<div class="text-center py-3 text-muted small">No gateway transactions recorded</div>';
                return;
            }

            const labels = gateways.map(g => (g.method_name || 'Gateway') + ' (' + g.currency + ')');
            const series = gateways.map(g => parseFloat(g.total_amount || 0));

            const options = {
                chart: {
                    type: 'donut',
                    height: 190
                },
                labels: labels,
                series: series,
                colors: ['#7367f0', '#28c76f', '#00cfe8', '#ff9f43', '#ea5455', '#4b4b4b'],
                legend: { show: false },
                dataLabels: { enabled: false },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '72%',
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'Total',
                                    formatter: function(w) {
                                        const sum = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                        return '$' + sum.toFixed(0);
                                    }
                                }
                            }
                        }
                    }
                },
                tooltip: {
                    y: {
                        formatter: val => '$' + parseFloat(val).toFixed(2)
                    }
                }
            };

            ensureApexCharts(function() {
                if (gatewaysChart) {
                    gatewaysChart.destroy();
                }
                el.innerHTML = '';
                gatewaysChart = new ApexCharts(el, options);
                gatewaysChart.render();
            });
        }

        // Initial chart renders
        const initialChartData = <?= !empty($initialChart) ? json_encode($initialChart) : 'null'; ?>;
        if (initialChartData && initialChartData.categories && initialChartData.categories.length > 0) {
            renderTimelineChart(initialChartData);
        } else {
            loadTimelineChart(currentDays, currentCurrency);
        }

        <?php if (!empty($gateways)): ?>
            renderGatewaysChart(<?= json_encode($gateways); ?>);
        <?php endif; ?>

        // Range buttons (7D, 30D, 90D)
        $('#chartRangeButtons button').on('click', function() {
            $('#chartRangeButtons button').removeClass('active');
            $(this).addClass('active');
            currentDays = parseInt($(this).data('days'), 10) || 30;
            loadTimelineChart(currentDays, currentCurrency);
        });

        // 4. Currency Filter Handler
        $('#currencyDropdownMenu a').on('click', function() {
            $('#currencyDropdownMenu a').removeClass('active');
            $(this).addClass('active');
            currentCurrency = $(this).data('currency') || '';
            const label = currentCurrency ? currentCurrency : 'All Currencies';
            $('#currentCurrencyLabel').text(label);

            refreshFinanceStats();
            loadTimelineChart(currentDays, currentCurrency);

            let exportUrl = 'api?action=billing_finance_export';
            if (currentCurrency) exportUrl += '&currency=' + encodeURIComponent(currentCurrency);
            $('#btnExportLedger').attr('href', exportUrl);
        });

        // 5. Date Range Filter Handler
        $('#dateRangeDropdownMenu a').on('click', function() {
            $('#dateRangeDropdownMenu a').removeClass('active');
            $(this).addClass('active');
            currentRange = $(this).data('range') || 'all';
            $('#currentDateRangeLabel').text($(this).text());

            refreshFinanceStats();
        });

        function calculateDateBounds(range) {
            const now = Math.floor(Date.now() / 1000);
            let startDate = null;
            let endDate = null;

            if (range === 'today') {
                const start = new Date();
                start.setHours(0, 0, 0, 0);
                startDate = Math.floor(start.getTime() / 1000);
            } else if (range === 'yesterday') {
                const start = new Date();
                start.setDate(start.getDate() - 1);
                start.setHours(0, 0, 0, 0);
                const end = new Date();
                end.setDate(end.getDate() - 1);
                end.setHours(23, 59, 59, 999);
                startDate = Math.floor(start.getTime() / 1000);
                endDate = Math.floor(end.getTime() / 1000);
            } else if (range === '7days') {
                startDate = now - (7 * 86400);
            } else if (range === '30days') {
                startDate = now - (30 * 86400);
            } else if (range === 'this_month') {
                const start = new Date();
                start.setDate(1);
                start.setHours(0, 0, 0, 0);
                startDate = Math.floor(start.getTime() / 1000);
            }
            return { startDate, endDate };
        }

        function refreshFinanceStats() {
            const bounds = calculateDateBounds(currentRange);
            let url = 'api?action=billing_finance_stats';
            if (bounds.startDate) url += '&start_date=' + bounds.startDate;
            if (bounds.endDate) url += '&end_date=' + bounds.endDate;
            if (currentCurrency) url += '&currency=' + encodeURIComponent(currentCurrency);

            fetch(url)
                .then(r => r.json())
                .then(res => {
                    if (res.status !== 'success' || !res.data) return;
                    const d = res.data;
                    const stats = d.overview || {};

                    // Update Sales Container
                    let salesHtml = '';
                    if (stats.sales_by_currency && Object.keys(stats.sales_by_currency).length > 0) {
                        for (const curr in stats.sales_by_currency) {
                            const val = stats.sales_by_currency[curr].revenue;
                            salesHtml += `<h3 class="mb-0 fw-bold text-success d-inline-block me-2">
                                ${parseFloat(val).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                                <small class="fs-6">${curr}</small>
                            </h3>`;
                        }
                    } else {
                        salesHtml = '<h3 class="mb-0 fw-bold text-success">0.00 <small class="fs-6">USD</small></h3>';
                    }
                    $('#kpiSalesContainer').html(salesHtml);
                    $('#kpiApprovedSubsCount').text(stats.approved_sub_orders || 0);

                    // Update Net Profit Card
                    $('#kpiEstimatedProfit').html(parseFloat(stats.estimated_profit || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' <small class="fs-6">USD</small>');

                    // Update Points Bought Card
                    $('#kpiPointsBought').html(parseFloat(stats.total_points_bought || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' <small class="fs-6">pts</small>');

                    // Update Points Consumed Card
                    $('#kpiPointsConsumed').html(parseFloat(stats.direct_points_consumed || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' <small class="fs-6">pts</small>');
                    $('#kpiActionsCount').text(stats.direct_actions_count || 0);

                    // Update Gateways Donut
                    if (d.gateways) {
                        renderGatewaysChart(d.gateways);
                    }
                })
                .catch(err => console.error('Failed to refresh reseller finance stats:', err));
        }

        // 6. Statement Modal Handler
        const stmtModalEl = document.getElementById('statementModal');
        const stmtModal = stmtModalEl ? new bootstrap.Modal(stmtModalEl) : null;

        $(document).on('click', '.btn-statement', function() {
            const userId = $(this).data('user-id');
            const username = $(this).data('username') || '#' + userId;

            $('#statementModalSubtitle').text('Account: ' + username + ' (ID #' + userId + ')');
            $('#stmtUsername').text(username);
            $('#stmtEmail').text('Loading...');
            $('#stmtTotalCashPaid').text('0.00');
            $('#stmtTotalIn').text('+0.00');
            $('#stmtTotalOut').text('-0.00');
            $('#stmtCurrentBalance').text('0.00');
            $('#statementTbody').html('<tr><td colspan="7" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Loading statement details...</td></tr>');

            if (stmtModal) {
                stmtModal.show();
            }

            fetch('api?action=billing_finance_statement&user_id=' + encodeURIComponent(userId))
                .then(r => r.json())
                .then(res => {
                    if (res.status !== 'success' || !res.data) {
                        $('#statementTbody').html('<tr><td colspan="7" class="text-center py-4 text-danger">' + (res.message || 'Failed to load statement.') + '</td></tr>');
                        return;
                    }

                    const d = res.data;
                    const u = d.user || {};

                    $('#stmtUsername').text(u.username || '#' + userId);
                    $('#stmtEmail').text(u.email || 'No email registered');
                    $('#stmtTotalCashPaid').text(parseFloat(d.total_cash_paid || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                    $('#stmtTotalIn').text('+' + parseFloat(d.total_points_in || 0).toFixed(2));
                    $('#stmtTotalOut').text('-' + parseFloat(d.total_points_out || 0).toFixed(2));
                    $('#stmtCurrentBalance').text(parseFloat(d.current_balance || 0).toFixed(2));

                    const entries = d.entries || [];
                    if (entries.length === 0) {
                        $('#statementTbody').html('<tr><td colspan="7" class="text-center py-4 text-muted">No financial transactions recorded for this account.</td></tr>');
                        return;
                    }

                    let rowsHtml = '';
                    entries.forEach(e => {
                        const dateStr = e.timestamp ? new Date(e.timestamp * 1000).toLocaleString() : '-';
                        const isDeposit = e.entry_type === 'deposit';
                        const isUsage = e.entry_type === 'consumption';
                        const isIn = parseFloat(e.points_in || 0) > 0;
                        const isOut = parseFloat(e.points_out || 0) > 0;

                        let badgeClass = 'bg-label-primary';
                        if (isDeposit) badgeClass = 'bg-label-success';
                        else if (isUsage) badgeClass = 'bg-label-danger';
                        else badgeClass = 'bg-label-warning';

                        rowsHtml += `<tr>
                            <td><small class="text-muted">${dateStr}</small></td>
                            <td><span class="badge bg-label-secondary font-monospace">${e.ref_code || e.order_code || '-'}</span></td>
                            <td>
                                <span class="badge ${badgeClass} me-1">${e.entry_type}</span>
                                <span class="fw-semibold text-dark">${e.title || 'Transaction'}</span>
                            </td>
                            <td class="text-end font-monospace text-success fw-bold">
                                ${isIn ? '+' + parseFloat(e.points_in).toFixed(2) : '-'}
                            </td>
                            <td class="text-end font-monospace text-danger fw-bold">
                                ${isOut ? '-' + parseFloat(e.points_out).toFixed(2) : '-'}
                            </td>
                            <td class="text-end font-monospace text-primary">
                                ${parseFloat(e.fiat_amount || 0) > 0 ? parseFloat(e.fiat_amount).toFixed(2) + ' ' + (e.currency || 'USD') : '-'}
                            </td>
                            <td class="text-center">
                                <span class="badge bg-label-success">${e.status || 'Completed'}</span>
                            </td>
                        </tr>`;
                    });

                    $('#statementTbody').html(rowsHtml);
                })
                .catch(err => {
                    $('#statementTbody').html('<tr><td colspan="7" class="text-center py-4 text-danger">Network error loading statement.</td></tr>');
                });
        });

        // 7. Print Statement Handler
        $('#statementPrintBtn').on('click', function() {
            const printContent = document.getElementById('statementPrintArea').innerHTML;
            const printWindow = window.open('', '', 'width=900,height=650');
            printWindow.document.write('<html><head><title>Statement of Account</title>');
            printWindow.document.write('<link rel="stylesheet" href="assets/vendor/css/core.css">');
            printWindow.document.write('<style>body { font-family: sans-serif; padding: 25px; } table { width: 100%; border-collapse: collapse; } th, td { padding: 8px; border: 1px solid #ddd; } .text-end { text-align: right; } .text-center { text-align: center; }</style>');
            printWindow.document.write('</head><body>');
            printWindow.document.write('<h3 style="margin-bottom: 5px;">XC_VM Billing — Financial Statement of Account</h3>');
            printWindow.document.write('<p style="color: #666; margin-bottom: 20px;">Generated on: ' + new Date().toLocaleString() + '</p>');
            printWindow.document.write(printContent);
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            printWindow.focus();
            setTimeout(() => {
                printWindow.print();
                printWindow.close();
            }, 350);
        });
    });
})();
</script>
