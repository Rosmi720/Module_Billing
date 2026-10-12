<script id="scripts">
(function() {
    var toast = window.xcToast || function(msg, type) {
        if (window.Swal) {
            Swal.fire({ text: msg, icon: (type === 'error' ? 'error' : (type === 'warning' ? 'warning' : 'success')) });
        } else {
            alert(msg);
        }
    };

    var confirmDialog = function(title, text, confirmBtnText, onConfirm) {
        if (window.xcConfirm) {
            window.xcConfirm(title, text, onConfirm, { confirmButtonText: confirmBtnText });
        } else if (window.Swal) {
            Swal.fire({
                title: title,
                text: text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: confirmBtnText,
                cancelButtonText: 'Cancel'
            }).then(function(r) {
                if (r.isConfirmed) onConfirm();
            });
        } else {
            if (confirm(title + '\n' + text)) onConfirm();
        }
    };

    $(function() {
        if ($.fn.dataTable) {
            $.fn.dataTable.ext.errMode = 'none';
        }

        if ($.fn.DataTable && $('#pendingOrdersTable').length) {
            $('#pendingOrdersTable').DataTable({
                responsive: true,
                order: [[6, 'desc']], // Sorted by Date column
                columnDefs: [
                    { targets: 'no-sort', orderable: false, searchable: false }
                ],
                pageLength: 10,
                language: {
                    emptyTable: '<div class="d-flex flex-column align-items-center justify-content-center text-center py-4"><div class="avatar avatar-xl bg-label-success rounded-circle mb-3 d-flex align-items-center justify-content-center shadow-sm"><i class="ti tabler-circle-check fs-1 text-success"></i></div><h5 class="fw-bold mb-1 text-dark">All Caught Up!</h5><p class="text-muted mb-0">No pending points purchase requests from your sub-resellers.</p></div>'
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

        const modalEl = document.getElementById('resellerReviewModal');
        const modal = modalEl ? new bootstrap.Modal(modalEl) : null;
        let activeOrderId = null;
        let requiredPoints = 0;
        let myCredits = 0;

        $(document).on('click', '.review-order-btn', function() {
            activeOrderId = this.getAttribute('data-id');
            fetch('api?action=billing_get_order&id=' + encodeURIComponent(activeOrderId))
                .then(r => r.json())
                .then(res => {
                    if (res.status === 'success') {
                        const o = res.data;
                        requiredPoints = parseFloat(o.points_requested);
                        myCredits = parseFloat(o.seller_credits_balance || 0);

                        document.getElementById('resModalCode').textContent = o.order_code;
                        document.getElementById('resModalBuyer').textContent = o.buyer_username || '#' + o.user_id;
                        document.getElementById('resModalBuyerPoints').textContent = parseFloat(o.buyer_current_credits || 0).toFixed(2);
                        document.getElementById('resModalMyPoints').textContent = myCredits.toFixed(2);
                        document.getElementById('resModalPointsReq').textContent = requiredPoints.toFixed(2);
                        document.getElementById('resModalMethod').textContent = o.method_name;
                        document.getElementById('resModalCost').textContent = parseFloat(o.price_total).toFixed(2) + ' ' + (o.currency || 'USD');
                        document.getElementById('resModalTxRef').textContent = o.transaction_reference || 'None provided';
                        document.getElementById('resModalNotes').textContent = o.sender_notes || 'No notes.';

                        if (o.receipt_image) {
                            document.getElementById('resModalReceiptWrap').classList.remove('d-none');
                            const receiptUrl = 'billing_receipt?id=' + encodeURIComponent(o.id);
                            document.getElementById('resModalReceiptImg').src = receiptUrl;
                            document.getElementById('resModalReceiptLink').href = receiptUrl;
                            document.getElementById('resModalReceiptBtn').href = receiptUrl;
                        } else {
                            document.getElementById('resModalReceiptWrap').classList.add('d-none');
                        }

                        // Check balance sufficiency
                        const warning = document.getElementById('resBalanceWarning');
                        const approveBtn = document.getElementById('btnResApprove');
                        if (myCredits < requiredPoints) {
                            warning.classList.remove('d-none');
                            approveBtn.disabled = true;
                        } else {
                            warning.classList.add('d-none');
                            approveBtn.disabled = false;
                        }

                        if (modal) modal.show();
                    } else {
                        toast(res.message || 'Error loading order details.', 'error');
                    }
                })
                .catch(() => toast('Network error loading order.', 'error'));
        });

        $('#btnResApprove').on('click', function() {
            const btn = $(this);
            confirmDialog(
                'Confirm Approval',
                'Are you sure you want to approve this order? ' + requiredPoints.toFixed(2) + ' points will be deducted from your wallet and transferred to the sub-reseller.',
                'Yes, Transfer Points',
                function() {
                    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Processing...');

                    fetch('api?action=billing_approve_order&id=' + encodeURIComponent(activeOrderId), { method: 'POST' })
                        .then(r => r.json())
                        .then(res => {
                            if (res.status === 'success') {
                                toast(res.message || 'Points transferred successfully!', 'success');
                                setTimeout(() => location.reload(), 1000);
                            } else {
                                if (res.code === 'insufficient_balance') {
                                    if (window.Swal) {
                                        Swal.fire({
                                            icon: 'warning',
                                            title: 'Insufficient Points Balance!',
                                            html: '<p class="mb-2">' + (res.message || '') + '</p><p class="text-muted small">You need to top up your balance from the Admin before you can fulfill this sub-reseller order.</p>',
                                            showCancelButton: true,
                                            confirmButtonText: '<i class="ti tabler-plus me-1"></i> Top Up Balance Now',
                                            cancelButtonText: 'Cancel',
                                            customClass: {
                                                confirmButton: 'btn btn-success me-2',
                                                cancelButton: 'btn btn-label-secondary'
                                            },
                                            buttonsStyling: false
                                        }).then(function(result) {
                                            if (result.isConfirmed) {
                                                window.location.href = 'billing_buy';
                                            }
                                        });
                                    } else {
                                        toast(res.message, 'warning');
                                    }
                                } else {
                                    toast(res.message || 'Approval failed', 'error');
                                }
                                btn.prop('disabled', false).html('<i class="ti tabler-check me-1"></i> Approve & Transfer Points');
                            }
                        })
                        .catch(() => {
                            toast('Network error during approval.', 'error');
                            btn.prop('disabled', false).html('<i class="ti tabler-check me-1"></i> Approve & Transfer Points');
                        });
                }
            );
        });

        $('#btnResReject').on('click', function() {
            if (window.Swal) {
                Swal.fire({
                    title: 'Reject Order Request',
                    input: 'textarea',
                    inputLabel: 'Reason for rejection (shown to sub-reseller)',
                    inputPlaceholder: 'Enter reason for rejecting this order...',
                    inputAttributes: { autocapitalize: 'off' },
                    showCancelButton: true,
                    confirmButtonText: 'Reject Order',
                    confirmButtonColor: '#ea5455',
                    cancelButtonText: 'Cancel',
                    inputValidator: (value) => {
                        if (!value || !value.trim()) {
                            return 'Please provide a reason for the sub-reseller.';
                        }
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const reason = result.value;
                        fetch('api?action=billing_reject_order&id=' + encodeURIComponent(activeOrderId) + '&reason=' + encodeURIComponent(reason), { method: 'POST' })
                            .then(r => r.json())
                            .then(res => {
                                if (res.status === 'success') {
                                    toast(res.message || 'Order rejected.', 'warning');
                                    setTimeout(() => location.reload(), 1000);
                                } else {
                                    toast(res.message || 'Error rejecting order', 'error');
                                }
                            })
                            .catch(() => toast('Network error during rejection.', 'error'));
                    }
                });
            } else {
                const reason = prompt('Please enter the rejection reason for the sub-reseller:');
                if (reason === null) return;

                fetch('api?action=billing_reject_order&id=' + encodeURIComponent(activeOrderId) + '&reason=' + encodeURIComponent(reason), { method: 'POST' })
                    .then(r => r.json())
                    .then(res => {
                        if (res.status === 'success') {
                            toast(res.message || 'Order rejected.', 'warning');
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            toast(res.message || 'Error rejecting order', 'error');
                        }
                    });
            }
        });
    });
})();
</script>
</body>
</html>
