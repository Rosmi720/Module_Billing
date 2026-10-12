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

        if ($.fn.DataTable && $('#resellerOrdersTable').length) {
            $('#resellerOrdersTable').DataTable({
                responsive: true,
                order: [[7, 'desc']], // Sorted by Date desc
                columnDefs: [
                    { targets: 'no-sort', orderable: false, searchable: false }
                ],
                pageLength: 25,
                language: {
                    emptyTable: '<div class="d-flex flex-column align-items-center justify-content-center text-center py-4"><div class="avatar avatar-xl bg-label-secondary rounded-circle mb-3 d-flex align-items-center justify-content-center shadow-sm"><i class="ti tabler-inbox fs-1 text-secondary"></i></div><h5 class="fw-bold mb-1 text-dark">No Orders Found</h5><p class="text-muted mb-0">No purchase orders found matching this filter.</p></div>'
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

        const modalEl = document.getElementById('orderInspectModal');
        const modal = modalEl ? new bootstrap.Modal(modalEl) : null;
        let activeId = null;
        let reqPoints = 0;
        let myCredits = 0;

        $(document).on('click', '.inspect-order-btn', function() {
            activeId = this.getAttribute('data-id');
            fetch('api?action=billing_get_order&id=' + encodeURIComponent(activeId))
                .then(r => r.json())
                .then(res => {
                    if (res.status === 'success') {
                        const o = res.data;
                        reqPoints = parseFloat(o.points_requested);
                        myCredits = parseFloat(o.seller_credits_balance || 0);

                        document.getElementById('inspCode').textContent = o.order_code;
                        document.getElementById('inspBuyer').textContent = o.buyer_username || '#' + o.user_id;
                        document.getElementById('inspBuyerPoints').textContent = parseFloat(o.buyer_current_credits || 0).toFixed(2);
                        document.getElementById('inspSellerBalance').textContent = myCredits.toFixed(2);
                        document.getElementById('inspPointsReq').textContent = reqPoints.toFixed(2);
                        document.getElementById('inspMethod').textContent = o.method_name;
                        document.getElementById('inspCost').textContent = parseFloat(o.price_total).toFixed(2) + ' ' + (o.currency || 'USD');
                        document.getElementById('inspTxRef').textContent = o.transaction_reference || 'None provided';
                        document.getElementById('inspNotes').textContent = o.sender_notes || 'No notes.';

                        if (o.receipt_image) {
                            document.getElementById('inspReceiptWrap').classList.remove('d-none');
                            const receiptUrl = 'billing_receipt?id=' + encodeURIComponent(o.id);
                            document.getElementById('inspReceiptImg').src = receiptUrl;
                            document.getElementById('inspReceiptLink').href = receiptUrl;
                            document.getElementById('inspReceiptBtn').href = receiptUrl;
                        } else {
                            document.getElementById('inspReceiptWrap').classList.add('d-none');
                        }

                        const pendingActions = document.getElementById('inspPendingActions');
                        const closedStatus = document.getElementById('inspClosedStatus');
                        const rejWrap = document.getElementById('inspRejectionWrap');
                        const warning = document.getElementById('inspBalanceWarning');
                        const approveBtn = document.getElementById('btnInspApprove');

                        rejWrap.classList.add('d-none');
                        warning.classList.add('d-none');

                        if (o.status === 'pending') {
                            pendingActions.classList.remove('d-none');
                            closedStatus.classList.add('d-none');
                            if (myCredits < reqPoints) {
                                warning.classList.remove('d-none');
                                approveBtn.disabled = true;
                            } else {
                                approveBtn.disabled = false;
                            }
                        } else {
                            pendingActions.classList.add('d-none');
                            closedStatus.classList.remove('d-none');
                            const badge = document.getElementById('inspStatusBadge');
                            if (o.status === 'approved') {
                                badge.innerHTML = '<span class="badge bg-label-success fs-6"><i class="ti tabler-check me-1"></i>Approved</span>';
                            } else {
                                badge.innerHTML = '<span class="badge bg-label-danger fs-6"><i class="ti tabler-x me-1"></i>Rejected</span>';
                                rejWrap.classList.remove('d-none');
                                document.getElementById('inspRejectionReason').textContent = o.rejection_reason || 'N/A';
                            }
                        }

                        if (modal) modal.show();
                    } else {
                        toast(res.message || 'Error loading order details.', 'error');
                    }
                })
                .catch(() => toast('Network error while inspecting order.', 'error'));
        });

        $('#btnInspApprove').on('click', function() {
            var btn = this;
            confirmDialog('Approve and transfer ' + reqPoints.toFixed(2) + ' points now?').then(function(ok) {
                if (!ok) return;
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Processing...';

                fetch('api?action=billing_approve_order&id=' + encodeURIComponent(activeId), { method: 'POST' })
                    .then(r => r.json())
                    .then(res => {
                        if (res.status === 'success') {
                            toast(res.message || 'Points transferred successfully!', 'success');
                            setTimeout(() => location.reload(), 700);
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
                                toast(res.message || 'Approval error', 'error');
                            }
                            btn.disabled = false;
                            btn.innerHTML = '<i class="ti tabler-check me-1"></i> Approve & Transfer Points';
                        }
                    })
                    .catch(() => {
                        toast('Network error during approval.', 'error');
                        btn.disabled = false;
                        btn.innerHTML = '<i class="ti tabler-check me-1"></i> Approve & Transfer Points';
                    });
            });
        });

        $('#btnInspReject').on('click', function() {
            var handleReject = function(reason) {
                if (reason === null) return;
                fetch('api?action=billing_reject_order&id=' + encodeURIComponent(activeId) + '&reason=' + encodeURIComponent(reason), { method: 'POST' })
                    .then(r => r.json())
                    .then(res => {
                        if (res.status === 'success') {
                            toast(res.message || 'Order rejected.', 'success');
                            setTimeout(() => location.reload(), 700);
                        } else {
                            toast(res.message || 'Error rejecting order', 'error');
                        }
                    })
                    .catch(() => toast('Network error during rejection.', 'error'));
            };

            if (window.Swal) {
                Swal.fire({
                    title: 'Reject Request',
                    text: 'Please specify the rejection reason:',
                    input: 'text',
                    inputPlaceholder: 'e.g. Payment not received / incorrect transfer receipt',
                    showCancelButton: true,
                    confirmButtonText: 'Confirm Rejection',
                    customClass: {
                        confirmButton: 'btn btn-danger',
                        cancelButton: 'btn btn-label-secondary ms-2'
                    },
                    buttonsStyling: false
                }).then(function(result) {
                    if (result.isConfirmed) {
                        handleReject(result.value || 'Rejected by distributor');
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
