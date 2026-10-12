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

        if ($.fn.DataTable && $('#recentOrdersTable').length) {
            $('#recentOrdersTable').DataTable({
                responsive: true,
                order: [[7, 'desc']], // Sorted by Date column
                columnDefs: [
                    { targets: 'no-sort', orderable: false, searchable: false }
                ],
                pageLength: 10,
                language: {
                    emptyTable: '<div class="d-flex flex-column align-items-center justify-content-center text-center py-4"><div class="avatar avatar-xl bg-label-secondary rounded-circle mb-3 d-flex align-items-center justify-content-center shadow-sm"><i class="ti tabler-inbox fs-1 text-secondary"></i></div><h5 class="fw-bold mb-1 text-dark">No Recent Orders</h5><p class="text-muted mb-0">No point purchase transactions have been recorded yet.</p></div>'
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

        const modalEl = document.getElementById('orderDetailsModal');
        const modal = modalEl ? new bootstrap.Modal(modalEl) : null;

        $(document).on('click', '.view-order-btn', function() {
            const orderId = this.getAttribute('data-id');
            fetch('api?action=billing_get_order&id=' + encodeURIComponent(orderId))
                .then(r => r.json())
                .then(res => {
                    if (res.status === 'success') {
                        const o = res.data;
                        document.getElementById('modalOrderCode').textContent = o.order_code;
                        document.getElementById('modalBuyer').textContent = o.buyer_username || '#' + o.user_id;
                        document.getElementById('modalBuyerPoints').textContent = parseFloat(o.buyer_current_credits || 0).toFixed(2);
                        document.getElementById('modalSeller').textContent = o.seller_username || (o.seller_id == 0 ? 'System / Admin' : '#' + o.seller_id);
                        document.getElementById('modalPointsRequested').textContent = parseFloat(o.points_requested).toFixed(2);
                        document.getElementById('modalMethod').textContent = o.method_name;
                        document.getElementById('modalCost').textContent = parseFloat(o.price_total).toFixed(2) + ' ' + (o.currency || 'USD');
                        document.getElementById('modalTxRef').textContent = o.transaction_reference || 'None provided';
                        document.getElementById('modalSenderNotes').textContent = o.sender_notes || 'No notes.';

                        if (o.receipt_image) {
                            document.getElementById('modalReceiptWrap').classList.remove('d-none');
                            const receiptUrl = 'billing_receipt?id=' + encodeURIComponent(o.id);
                            document.getElementById('modalReceiptImg').src = receiptUrl;
                            document.getElementById('modalReceiptLink').href = receiptUrl;
                            document.getElementById('modalReceiptBtn').href = receiptUrl;
                        } else {
                            document.getElementById('modalReceiptWrap').classList.add('d-none');
                        }

                        const statusBadge = document.getElementById('modalStatusBadge');
                        if (o.status === 'approved') {
                            statusBadge.className = 'badge bg-label-success fs-6';
                            statusBadge.innerHTML = '<i class="ti tabler-check me-1"></i>Approved';
                        } else if (o.status === 'pending') {
                            statusBadge.className = 'badge bg-label-warning fs-6';
                            statusBadge.innerHTML = '<i class="ti tabler-clock me-1"></i>Pending';
                        } else if (o.status === 'rejected') {
                            statusBadge.className = 'badge bg-label-danger fs-6';
                            statusBadge.innerHTML = '<i class="ti tabler-x me-1"></i>Rejected';
                            if (o.rejection_reason) {
                                document.getElementById('modalRejectionWrap').classList.remove('d-none');
                                document.getElementById('modalRejectionReason').textContent = o.rejection_reason;
                            }
                        }

                        if (modal) modal.show();
                    } else {
                        toast(res.message || 'Failed to load order details.', 'error');
                    }
                })
                .catch(() => toast('Network error loading order details.', 'error'));
        });
    });
})();
</script>
</body>
</html>
