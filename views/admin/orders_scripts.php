<script id="scripts">
(function() {
    var toast = window.xcToast || function(msg, type) {
        if (window.Swal) {
            Swal.fire({ text: msg, icon: (type === 'error' ? 'error' : (type === 'warning' ? 'warning' : 'success')) });
        } else {
            alert(msg);
        }
    };

    var confirmDialog = window.xcConfirm || function(msg) {
        return Promise.resolve(confirm(msg));
    };

    $(function() {
        if ($.fn.dataTable) {
            $.fn.dataTable.ext.errMode = 'none';
        }

        // Initialize DataTables with Responsive and Export Buttons
        if ($.fn.DataTable && $('#billingOrdersTable').length) {
            $('#billingOrdersTable').DataTable({
                responsive: true,
                order: [[8, 'desc']],
                columnDefs: [
                    { targets: 'no-sort', orderable: false, searchable: false }
                ],
                pageLength: 25,
                language: {
                    emptyTable: '<div class="d-flex flex-column align-items-center justify-content-center text-center py-4"><div class="avatar avatar-xl bg-label-secondary rounded-circle mb-3 d-flex align-items-center justify-content-center shadow-sm"><i class="ti tabler-inbox fs-1 text-secondary"></i></div><h5 class="fw-bold mb-1 text-dark">No Orders Found</h5><p class="text-muted mb-0" style="max-width: 480px;">No purchase orders match the selected filter criteria.</p></div>'
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

        const modalEl = document.getElementById('adminOrderModal');
        const modal = modalEl ? new bootstrap.Modal(modalEl) : null;
        let currentOrderId = null;

        $(document).on('click', '.view-admin-order-btn', function() {
            currentOrderId = this.getAttribute('data-id');
            fetch('api?action=billing_get_order&id=' + encodeURIComponent(currentOrderId))
                .then(r => r.json())
                .then(res => {
                    if (res.status === 'success') {
                        const o = res.data;
                        document.getElementById('admModalCode').textContent = o.order_code;
                        document.getElementById('admModalBuyer').textContent = o.buyer_username || '#' + o.user_id;
                        document.getElementById('admModalBuyerPoints').textContent = parseFloat(o.buyer_current_credits || 0).toFixed(2);
                        document.getElementById('admModalSeller').textContent = o.seller_username || (o.seller_id == 0 ? 'Admin' : '#' + o.seller_id);
                        document.getElementById('admModalPoints').textContent = parseFloat(o.points_requested).toFixed(2);
                        document.getElementById('admModalMethod').textContent = o.method_name;
                        document.getElementById('admModalCost').textContent = parseFloat(o.price_total).toFixed(2) + ' ' + (o.currency || 'USD');
                        document.getElementById('admModalTxRef').textContent = o.transaction_reference || 'None provided';
                        document.getElementById('admModalNotes').textContent = o.sender_notes || 'No notes.';

                        if (o.receipt_image) {
                            document.getElementById('admModalReceiptWrap').classList.remove('d-none');
                            const receiptUrl = 'billing_receipt?id=' + encodeURIComponent(o.id);
                            document.getElementById('admModalReceiptImg').src = receiptUrl;
                            document.getElementById('admModalReceiptLink').href = receiptUrl;
                            document.getElementById('admModalReceiptBtn').href = receiptUrl;
                        } else {
                            document.getElementById('admModalReceiptWrap').classList.add('d-none');
                        }

                        const statusEl = document.getElementById('admModalStatus');
                        const actionSec = document.getElementById('admActionSection');

                        if (o.status === 'pending') {
                            statusEl.innerHTML = '<span class="badge bg-label-warning fs-6"><i class="ti tabler-clock me-1"></i>Pending</span>';
                            actionSec.classList.remove('d-none');
                        } else if (o.status === 'approved') {
                            statusEl.innerHTML = '<span class="badge bg-label-success fs-6"><i class="ti tabler-check me-1"></i>Approved</span>';
                            actionSec.classList.add('d-none');
                        } else {
                            statusEl.innerHTML = '<span class="badge bg-label-danger fs-6"><i class="ti tabler-x me-1"></i>Rejected</span>';
                            actionSec.classList.add('d-none');
                        }

                        if (modal) modal.show();
                    } else {
                        toast(res.message || 'Failed to load order details.', 'error');
                    }
                })
                .catch(() => toast('Network error while loading order.', 'error'));
        });

        $('#btnAdminApprove').on('click', function() {
            confirmDialog('Are you sure you want to approve this order and credit points to the user?').then(function(ok) {
                if (!ok) return;
                fetch('api?action=billing_approve_order&id=' + encodeURIComponent(currentOrderId), { method: 'POST' })
                    .then(r => r.json())
                    .then(res => {
                        if (res.status === 'success') {
                            toast(res.message || 'Order approved successfully!', 'success');
                            setTimeout(() => location.reload(), 800);
                        } else {
                            toast(res.message || 'Approval error', 'error');
                        }
                    })
                    .catch(() => toast('Network error during approval.', 'error'));
            });
        });

        $('#btnAdminReject').on('click', function() {
            var handleReject = function(reason) {
                if (reason === null) return;
                fetch('api?action=billing_reject_order&id=' + encodeURIComponent(currentOrderId) + '&reason=' + encodeURIComponent(reason), { method: 'POST' })
                    .then(r => r.json())
                    .then(res => {
                        if (res.status === 'success') {
                            toast(res.message || 'Order rejected successfully.', 'success');
                            setTimeout(() => location.reload(), 800);
                        } else {
                            toast(res.message || 'Rejection error', 'error');
                        }
                    })
                    .catch(() => toast('Network error during rejection.', 'error'));
            };

            if (window.Swal) {
                Swal.fire({
                    title: 'Reject Order',
                    text: 'Please specify the reason for rejecting this order:',
                    input: 'text',
                    inputPlaceholder: 'e.g. Invalid transaction receipt reference',
                    showCancelButton: true,
                    confirmButtonText: 'Confirm Rejection',
                    customClass: {
                        confirmButton: 'btn btn-danger',
                        cancelButton: 'btn btn-label-secondary ms-2'
                    },
                    buttonsStyling: false
                }).then(function(result) {
                    if (result.isConfirmed) {
                        handleReject(result.value || 'Rejected by administrator');
                    }
                });
            } else {
                var reason = prompt('Please enter rejection reason:');
                handleReject(reason);
            }
        });
    });
})();
</script>
</body>
</html>
