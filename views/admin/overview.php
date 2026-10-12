<?php
use XcVm\Core\Util\LayoutRenderer;
use XcVm\Core\Localization\Translator;

/** @var array $stats */
/** @var array $recentOrders */
$language = Translator::class;
?>
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="ti tabler-credit-card me-2"></i><?= htmlspecialchars($language::get('billing_admin_overview_title')); ?></h4>
            <p class="text-muted mb-0"><?= htmlspecialchars($language::get('billing_admin_overview_desc')); ?></p>
        </div>
        <div class="d-flex gap-2">
            <a href="billing_resellers" class="btn btn-primary">
                <i class="ti tabler-users me-1"></i> <?= htmlspecialchars($language::get('billing_admin_nav_resellers')); ?>
            </a>
            <a href="billing_methods" class="btn btn-outline-secondary">
                <i class="ti tabler-settings me-1"></i> <?= htmlspecialchars($language::get('billing_common_methods')); ?>
            </a>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted d-block mb-1"><?= htmlspecialchars($language::get('billing_admin_total_points_minted')); ?></span>
                        <h3 class="mb-0 fw-bold text-success"><?= number_format($stats['total_points_approved'] ?? 0, 2); ?></h3>
                    </div>
                    <div class="avatar avatar-lg bg-label-success rounded-circle p-2">
                        <i class="ti tabler-coin fs-2"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted d-block mb-1"><?= htmlspecialchars($language::get('billing_admin_total_volume')); ?></span>
                        <h3 class="mb-0 fw-bold text-primary"><?= number_format($stats['total_revenue'] ?? 0, 2); ?> <small class="fs-6">USD</small></h3>
                    </div>
                    <div class="avatar avatar-lg bg-label-primary rounded-circle p-2">
                        <i class="ti tabler-chart-arrows fs-2"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted d-block mb-1"><?= htmlspecialchars($language::get('billing_admin_pending_orders_count')); ?></span>
                        <h3 class="mb-0 fw-bold <?= (!empty($stats['pending_orders'])) ? 'text-warning' : 'text-muted'; ?>">
                            <?= intval($stats['pending_orders'] ?? 0); ?>
                        </h3>
                    </div>
                    <div class="avatar avatar-lg bg-label-warning rounded-circle p-2">
                        <i class="ti tabler-clock-hour-4 fs-2"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted d-block mb-1"><?= htmlspecialchars($language::get('billing_admin_active_methods_count')); ?></span>
                        <h3 class="mb-0 fw-bold text-info"><?= intval($stats['total_methods'] ?? 0); ?></h3>
                    </div>
                    <div class="avatar avatar-lg bg-label-info rounded-circle p-2">
                        <i class="ti tabler-wallet fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Orders Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center bg-transparent border-bottom">
            <h5 class="card-title mb-0"><i class="ti tabler-list-check me-2"></i><?= htmlspecialchars($language::get('billing_admin_orders_title')); ?></h5>
            <a href="billing_orders" class="btn btn-sm btn-outline-primary"><?= htmlspecialchars($language::get('billing_admin_nav_orders')); ?></a>
        </div>
        <div class="card-datatable table-responsive">
            <table class="table table-hover align-middle mb-0" id="recentOrdersTable" style="width:100%">
                <thead class="table-light">
                    <tr>
                        <th><?= htmlspecialchars($language::get('billing_common_order_code')); ?></th>
                        <th><?= htmlspecialchars($language::get('billing_reseller_buyer')); ?></th>
                        <th><?= htmlspecialchars($language::get('billing_admin_reseller_username')); ?></th>
                        <th><?= htmlspecialchars($language::get('billing_common_points')); ?></th>
                        <th><?= htmlspecialchars($language::get('billing_common_total')); ?></th>
                        <th><?= htmlspecialchars($language::get('billing_common_payment_method')); ?></th>
                        <th><?= htmlspecialchars($language::get('billing_common_status')); ?></th>
                        <th><?= htmlspecialchars($language::get('billing_common_date')); ?></th>
                        <th class="text-end no-sort"><?= htmlspecialchars($language::get('billing_common_actions')); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($recentOrders)): ?>
                        <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td>
                                    <span class="fw-semibold text-primary"><?= htmlspecialchars((string) $order['order_code']); ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-label-secondary"><?= htmlspecialchars((string) ($order['buyer_username'] ?: '#' . $order['user_id'])); ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-label-dark"><?= htmlspecialchars((string) ($order['seller_username'] ?: ($order['seller_id'] == 0 ? 'System / Admin' : '#' . $order['seller_id']))); ?></span>
                                </td>
                                <td>
                                    <span class="fw-bold text-success">+<?= number_format(floatval($order['points_requested']), 2); ?></span>
                                </td>
                                <td>
                                    <?= number_format(floatval($order['price_total']), 2); ?> <small class="text-muted"><?= htmlspecialchars((string) $order['currency']); ?></small>
                                </td>
                                <td>
                                    <i class="ti tabler-credit-card me-1 text-muted"></i><?= htmlspecialchars((string) $order['method_name']); ?>
                                </td>
                                <td>
                                    <?php if ($order['status'] === 'approved'): ?>
                                        <span class="badge bg-label-success"><i class="ti tabler-check me-1"></i><?= htmlspecialchars($language::get('billing_common_status_approved')); ?></span>
                                    <?php elseif ($order['status'] === 'pending'): ?>
                                        <span class="badge bg-label-warning"><i class="ti tabler-clock me-1"></i><?= htmlspecialchars($language::get('billing_common_status_pending')); ?></span>
                                    <?php elseif ($order['status'] === 'rejected'): ?>
                                        <span class="badge bg-label-danger"><i class="ti tabler-x me-1"></i><?= htmlspecialchars($language::get('billing_common_status_rejected')); ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-label-secondary"><?= htmlspecialchars((string) $order['status']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted small">
                                    <?= date('Y-m-d H:i', intval($order['created_at'])); ?>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-label-primary view-order-btn" data-id="<?= (int) $order['id']; ?>" title="View Order">
                                        <i class="ti tabler-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Order Details & Receipt Preview -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title"><i class="ti tabler-file-invoice me-2"></i><?= htmlspecialchars($language::get('billing_reseller_review_modal_title')); ?>: <span id="modalOrderCode" class="text-primary"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block"><?= htmlspecialchars($language::get('billing_reseller_buyer')); ?></small>
                            <span id="modalBuyer" class="fw-bold fs-6"></span>
                            <div class="mt-2 small text-muted"><?= htmlspecialchars($language::get('billing_reseller_buyer_balance')); ?>: <span id="modalBuyerPoints" class="fw-semibold text-success"></span></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block"><?= htmlspecialchars($language::get('billing_admin_reseller_username')); ?></small>
                            <span id="modalSeller" class="fw-bold fs-6"></span>
                            <div class="mt-2 small text-muted"><?= htmlspecialchars($language::get('billing_reseller_points_requested')); ?>: <span id="modalPointsRequested" class="fw-bold text-primary"></span></div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <small class="text-muted d-block"><?= htmlspecialchars($language::get('billing_common_payment_method')); ?></small>
                        <span id="modalMethod" class="fw-semibold"></span>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block"><?= htmlspecialchars($language::get('billing_common_total')); ?></small>
                        <span id="modalCost" class="fw-semibold text-success"></span>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block"><?= htmlspecialchars($language::get('billing_common_reference')); ?></small>
                        <span id="modalTxRef" class="badge bg-label-info font-monospace fs-6"></span>
                    </div>
                </div>

                <div class="mb-4" id="modalNotesWrap">
                    <small class="text-muted d-block mb-1"><?= htmlspecialchars($language::get('billing_common_notes')); ?></small>
                    <div id="modalSenderNotes" class="p-2 border rounded bg-white small text-break"></div>
                </div>

                <div class="mb-4" id="modalReceiptWrap">
                    <small class="text-muted d-block mb-2"><i class="ti tabler-paperclip me-1"></i><?= htmlspecialchars($language::get('billing_reseller_attached_receipt')); ?></small>
                    <div class="text-center p-3 border rounded bg-light">
                        <a id="modalReceiptLink" href="#" target="_blank" class="d-inline-block">
                            <img id="modalReceiptImg" src="" alt="Receipt" class="img-fluid rounded shadow-sm" style="max-height: 350px;">
                        </a>
                        <div class="mt-2">
                            <a id="modalReceiptBtn" href="#" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="ti tabler-external-link me-1"></i><?= htmlspecialchars($language::get('billing_reseller_open_receipt_tab')); ?>
                            </a>
                        </div>
                    </div>
                </div>

                <div id="modalRejectionWrap" class="alert alert-danger d-none">
                    <strong><?= htmlspecialchars($language::get('billing_common_rejection_reason')); ?>:</strong> <span id="modalRejectionReason"></span>
                </div>
            </div>
            <div class="modal-footer border-top d-flex justify-content-between">
                <span id="modalStatusBadge" class="fs-6"></span>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= htmlspecialchars($language::get('billing_common_close')); ?></button>
            </div>
        </div>
    </div>
</div>

<?php
LayoutRenderer::renderFooter('admin');
include __DIR__ . '/overview_scripts.php';
?>
