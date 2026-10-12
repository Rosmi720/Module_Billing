<script id="scripts">
(function() {
    var toast = window.xcToast || function(msg, type) {
        if (window.Swal) {
            Swal.fire({ text: msg, icon: (type === 'error' ? 'error' : (type === 'warning' ? 'warning' : 'success')) });
        } else {
            alert(msg);
        }
    };

    $(function() {
        if ($.fn.dataTable) {
            $.fn.dataTable.ext.errMode = 'none';
        }

        if ($.fn.DataTable && $('#resellersTable').length) {
            $('#resellersTable').DataTable({
                responsive: true,
                order: [[3, 'desc']], // Sorted by Current Points desc
                columnDefs: [
                    { targets: 'no-sort', orderable: false, searchable: false }
                ],
                pageLength: 25,
                language: {
                    emptyTable: '<div class="d-flex flex-column align-items-center justify-content-center text-center py-4"><div class="avatar avatar-xl bg-label-secondary rounded-circle mb-3 d-flex align-items-center justify-content-center shadow-sm"><i class="ti tabler-user-off fs-1 text-secondary"></i></div><h5 class="fw-bold mb-1 text-dark">No Resellers Found</h5><p class="text-muted mb-0">No registered reseller accounts match your filter.</p></div>'
                },
                layout: {
                    topStart: {
                        pageLength: {},
                        buttons: [
                            { extend: 'copy', className: 'btn btn-sm btn-label-secondary me-1', text: '<i class="ti tabler-copy me-1"></i>Copy' },
                            { extend: 'csv', className: 'btn btn-sm btn-label-primary me-1', text: '<i class="ti tabler-file-spreadsheet me-1"></i>CSV' },
                            { extend: 'excel', className: 'btn btn-sm btn-label-success me-1', text: '<i class="ti tabler-file-analytics me-1"></i>Excel' },
                            { extend: 'pdf', className: 'btn btn-sm btn-label-danger me-1', text: '<i class="ti tabler-file-text me-1"></i>PDF' },
                            { extend: 'print', className: 'btn btn-sm btn-label-info', text: '<i class="ti tabler-printer me-1"></i>Print' }
                        ]
                    },
                    topEnd: 'search'
                }
            });
        }

        const drillModalEl = document.getElementById('drilldownModal');
        const drillModal = drillModalEl ? new bootstrap.Modal(drillModalEl) : null;

        $(document).on('click', '.drilldown-btn', function() {
            const resellerId = this.getAttribute('data-id');
            const resellerName = this.getAttribute('data-name');

            document.getElementById('drillResellerName').textContent = resellerName;
            document.getElementById('drillResellerId').textContent = resellerId;
            document.getElementById('drillResellerBalance').textContent = '...';

            if (drillModal) drillModal.show();

            fetch('api?action=billing_drilldown&reseller_id=' + encodeURIComponent(resellerId))
                .then(r => r.json())
                .then(res => {
                    if (res.status === 'success') {
                        const d = res.data;
                        document.getElementById('drillResellerBalance').textContent = parseFloat(d.reseller.credits || 0).toFixed(2);
                        document.getElementById('drillMethodsCount').textContent = d.methods.length;
                        document.getElementById('drillOrdersCount').textContent = d.orders.length;

                        // Methods table
                        const mBody = document.getElementById('drillMethodsTableBody');
                        if (d.methods.length === 0) {
                            mBody.innerHTML = '<tr><td colspan="6" class="text-center py-5"><div class="d-flex flex-column align-items-center justify-content-center text-center py-3"><div class="avatar avatar-lg bg-label-secondary rounded-circle mb-2 d-flex align-items-center justify-content-center shadow-xs"><i class="ti tabler-wallet-off fs-2 text-secondary"></i></div><h6 class="fw-bold mb-1">No Payment Methods</h6><p class="text-muted small mb-0">No payment methods configured by this reseller.</p></div></td></tr>';
                        } else {
                            mBody.innerHTML = d.methods.map(m => `
                                <tr>
                                    <td><i class="${m.icon || 'ti tabler-credit-card'} me-1 text-primary"></i><strong>${m.name}</strong></td>
                                    <td><code>${m.slug}</code></td>
                                    <td>${parseFloat(m.rate_per_point).toFixed(2)} ${m.currency}</td>
                                    <td>${m.min_points} / ${m.max_points > 0 ? m.max_points : '∞'}</td>
                                    <td><span class="badge bg-label-${m.active == 1 ? 'success' : 'secondary'}">${m.active == 1 ? 'Active' : 'Disabled'}</span></td>
                                    <td><button class="btn btn-xs btn-label-secondary view-drill-inst" data-inst="${encodeURIComponent(m.instructions || '')}">View Info</button></td>
                                </tr>
                            `).join('');
                        }

                        // Orders table
                        const oBody = document.getElementById('drillOrdersTableBody');
                        if (d.orders.length === 0) {
                            oBody.innerHTML = '<tr><td colspan="9" class="text-center py-5"><div class="d-flex flex-column align-items-center justify-content-center text-center py-3"><div class="avatar avatar-lg bg-label-secondary rounded-circle mb-2 d-flex align-items-center justify-content-center shadow-xs"><i class="ti tabler-inbox fs-2 text-secondary"></i></div><h6 class="fw-bold mb-1">No Orders</h6><p class="text-muted small mb-0">No purchase orders submitted to this reseller yet.</p></div></td></tr>';
                        } else {
                            oBody.innerHTML = d.orders.map(o => `
                                <tr>
                                    <td><span class="fw-semibold text-primary font-monospace">${o.order_code}</span></td>
                                    <td>${o.buyer_username || '#' + o.user_id}</td>
                                    <td><span class="fw-bold text-success">+${parseFloat(o.points_requested).toFixed(2)}</span></td>
                                    <td>${parseFloat(o.price_total).toFixed(2)} ${o.currency}</td>
                                    <td>${o.method_name}</td>
                                    <td><code>${o.transaction_reference || 'N/A'}</code></td>
                                    <td><span class="badge bg-label-${o.status === 'approved' ? 'success' : (o.status === 'pending' ? 'warning' : 'danger')}">${o.status}</span></td>
                                    <td><small class="text-muted">${new Date(o.created_at * 1000).toLocaleString()}</small></td>
                                    <td>
                                        ${o.receipt_image ? `<a href="billing_receipt?id=${o.id}" target="_blank" class="btn btn-xs btn-outline-primary"><i class="ti tabler-photo"></i> Receipt</a>` : '<span class="text-muted">-</span>'}
                                    </td>
                                </tr>
                            `).join('');
                        }

                        // Logs table
                        const lBody = document.getElementById('drillLogsTableBody');
                        if (d.logs.length === 0) {
                            lBody.innerHTML = '<tr><td colspan="5" class="text-center py-3 text-muted">No activity logs recorded.</td></tr>';
                        } else {
                            lBody.innerHTML = d.logs.map(l => `
                                <tr>
                                    <td><span class="badge bg-label-dark">${l.action}</span></td>
                                    <td>${l.target_username || (l.target_user_id ? '#' + l.target_user_id : '-')}</td>
                                    <td><small class="text-muted font-monospace">${l.details || ''}</small></td>
                                    <td><small>${l.ip_address || '-'}</small></td>
                                    <td><small class="text-muted">${new Date(l.created_at * 1000).toLocaleString()}</small></td>
                                </tr>
                            `).join('');
                        }
                    } else {
                        toast(res.message || 'Error fetching drill-down data.', 'error');
                    }
                })
                .catch(() => toast('Network error during drill-down fetch.', 'error'));
        });

        $(document).on('click', '.view-drill-inst', function() {
            var inst = decodeURIComponent(this.getAttribute('data-inst') || '');
            if (/&lt;[a-z!/]/i.test(inst) || (inst.includes('&lt;') && inst.includes('&gt;'))) {
                const txt = document.createElement('textarea');
                txt.innerHTML = inst;
                inst = txt.value;
            }
            if (window.Swal) {
                Swal.fire({
                    title: 'Payment Instructions',
                    html: inst || '<p class="text-muted">No instructions specified.</p>',
                    confirmButtonText: 'Close',
                    customClass: { confirmButton: 'btn btn-primary' },
                    buttonsStyling: false
                });
            } else {
                toast(inst || 'No instructions specified.', 'info');
            }
        });
    });
})();
</script>
</body>
</html>
