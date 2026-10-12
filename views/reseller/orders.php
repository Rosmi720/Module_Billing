<?php
use XcVm\Core\Util\LayoutRenderer;
use XcVm\Core\Localization\Translator;

/** @var array $orders */
$language = Translator::class;
?>
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="ti tabler-list-check me-2"></i><?= htmlspecialchars($language::get('billing_reseller_orders_title')); ?></h4>
            <p class="text-muted mb-0"><?= htmlspecialchars($language::get('billing_reseller_orders_desc')); ?></p>
        </div>
        <a href="billing" class="btn btn-outline-secondary">
            <i class="ti tabler-arrow-left me-1"></i> <?= htmlspecialchars($language::get('billing_subreseller_back_wallet')); ?>
        </a>
    </div>

    <!-- Filter Buttons -->
    <div class="d-flex gap-2 mb-4">
        <a href="billing_orders" class="btn btn-sm <?= empty($_GET['status']) ? 'btn-primary' : 'btn-outline-primary'; ?>"><?= htmlspecialchars($language::get('billing_common_all_statuses')); ?></a>
        <a href="billing_orders?status=pending" class="btn btn-sm <?= (($_GET['status'] ?? '') === 'pending') ? 'btn-warning' : 'btn-outline-warning'; ?>"><?= htmlspecialchars($language::get('billing_common_status_pending')); ?></a>
        <a href="billing_orders?status=approved" class="btn btn-sm <?= (($_GET['status'] ?? '') === 'approved') ? 'btn-success' : 'btn-outline-success'; ?>"><?= htmlspecialchars($language::get('billing_common_status_approved')); ?></a>
        <a href="billing_orders?status=rejected" class="btn btn-sm <?= (($_GET['status'] ?? '') === 'rejected') ? 'btn-danger' : 'btn-outline-danger'; ?>"><?= htmlspecialchars($language::get('billing_common_status_rejected')); ?></a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-datatable table-responsive">
            <table class="table table-hover align-middle mb-0" id="resellerOrdersTable" style="width:100%">
                <thead class="table-light">
                    <tr>
                        <th><?= htmlspecialchars($language::get('billing_common_order_code')); ?></th>
                        <th><?= htmlspecialchars($language::get('billing_reseller_buyer')); ?></th>
                        <th><?= htmlspecialchars($language::get('billing_common_points')); ?></th>
                        <th><?= htmlspecialchars($language::get('billing_common_total')); ?></th>
                        <th><?= htmlspecialchars($language::get('billing_common_payment_method')); ?></th>
                        <th><?= htmlspecialchars($language::get('billing_common_reference')); ?></th>
                        <th><?= htmlspecialchars($language::get('billing_common_status')); ?></th>
                        <th><?= htmlspecialchars($language::get('billing_common_date')); ?></th>
                        <th class="text-end no-sort"><?= htmlspecialchars($language::get('billing_common_actions')); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($orders)): ?>
                        <?php foreach ($orders as $o): ?>
                            <tr>
                                <td>
                                    <span class="fw-semibold text-primary font-monospace"><?= htmlspecialchars((string) $o['order_code']); ?></span>
                                </td>
                                <td>
                                    <span class="fw-bold text-dark"><?= htmlspecialchars((string) ($o['buyer_username'] ?: '#' . $o['user_id'])); ?></span>
                                </td>
                                <td>
                                    <span class="fw-bold text-success fs-6">+<?= number_format(floatval($o['points_requested']), 2); ?></span>
                                </td>
                                <td>
                                    <?= number_format(floatval($o['price_total']), 2); ?> <small class="text-muted"><?= htmlspecialchars((string) $o['currency']); ?></small>
                                </td>
                                <td>
                                    <i class="ti tabler-credit-card me-1 text-muted"></i><?= htmlspecialchars((string) $o['method_name']); ?>
                                </td>
                                <td>
                                    <span class="badge bg-label-secondary font-monospace"><?= htmlspecialchars((string) ($o['transaction_reference'] ?: '-')); ?></span>
                                </td>
                                <td>
                                    <?php if ($o['status'] === 'approved'): ?>
                                        <span class="badge bg-label-success"><i class="ti tabler-check me-1"></i><?= htmlspecialchars($language::get('billing_common_status_approved')); ?></span>
                                    <?php elseif ($o['status'] === 'pending'): ?>
                                        <span class="badge bg-label-warning"><i class="ti tabler-clock me-1"></i><?= htmlspecialchars($language::get('billing_common_status_pending')); ?></span>
                                    <?php elseif ($o['status'] === 'rejected'): ?>
                                        <span class="badge bg-label-danger"><i class="ti tabler-x me-1"></i><?= htmlspecialchars($language::get('billing_common_status_rejected')); ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-label-secondary"><?= htmlspecialchars((string) $o['status']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted">
                                    <?= date('Y-m-d H:i', intval($o['created_at'])); ?>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm <?= ($o['status'] === 'pending') ? 'btn-primary' : 'btn-label-secondary'; ?> inspect-order-btn" data-id="<?= (int) $o['id']; ?>">
                                        <i class="ti tabler-eye me-1"></i> <?= ($o['status'] === 'pending') ? htmlspecialchars($language::get('billing_reseller_review_credit')) : htmlspecialchars($language::get('billing_common_view')); ?>
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

<!-- Modal: Review / Inspect Order -->
<div class="modal fade" id="orderInspectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title"><i class="ti tabler-receipt me-2"></i><?= htmlspecialchars($language::get('billing_common_order')); ?>: <span id="inspCode" class="text-primary font-monospace"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block"><?= htmlspecialchars($language::get('billing_reseller_buyer')); ?></small>
                            <span id="inspBuyer" class="fw-bold fs-6"></span>
                            <div class="mt-2 small text-muted"><?= htmlspecialchars($language::get('billing_reseller_buyer_balance')); ?>: <span id="inspBuyerPoints" class="fw-semibold text-success"></span> credits</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-label-primary rounded">
                            <small class="text-muted d-block"><?= htmlspecialchars($language::get('billing_reseller_your_balance')); ?></small>
                            <span id="inspMyPoints" class="fw-bold fs-6 text-primary"></span> credits
                            <div class="mt-2 small text-muted"><?= htmlspecialchars($language::get('billing_reseller_points_to_transfer')); ?>: <span id="inspPointsReq" class="fw-bold text-success"></span> credits</div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <small class="text-muted d-block"><?= htmlspecialchars($language::get('billing_common_payment_method')); ?></small>
                        <span id="inspMethod" class="fw-semibold"></span>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block"><?= htmlspecialchars($language::get('billing_common_total')); ?></small>
                        <span id="inspCost" class="fw-semibold text-success fs-6"></span>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block"><?= htmlspecialchars($language::get('billing_common_reference')); ?></small>
                        <span id="inspTxRef" class="badge bg-label-info font-monospace fs-6"></span>
                    </div>
                </div>

                <div class="mb-4">
                    <small class="text-muted d-block mb-1"><?= htmlspecialchars($language::get('billing_reseller_buyer_notes')); ?></small>
                    <div id="inspNotes" class="p-2 border rounded bg-white small text-break"></div>
                </div>

                <div class="mb-4" id="inspReceiptWrap">
                    <small class="text-muted d-block mb-2"><i class="ti tabler-photo me-1"></i><?= htmlspecialchars($language::get('billing_reseller_attached_receipt')); ?></small>
                    <div class="text-center p-3 border rounded bg-light">
                        <a id="inspReceiptLink" href="#" target="_blank" class="d-inline-block">
                            <img id="inspReceiptImg" src="" alt="Receipt" class="img-fluid rounded shadow-sm" style="max-height: 380px;">
                        </a>
                        <div class="mt-2">
                            <a id="inspReceiptBtn" href="#" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="ti tabler-external-link me-1"></i><?= htmlspecialchars($language::get('billing_reseller_open_receipt_tab')); ?>
                            </a>
                        </div>
                    </div>
                </div>

                <div id="inspRejectionWrap" class="alert alert-danger d-none">
                    <strong><?= htmlspecialchars($language::get('billing_common_rejection_reason')); ?>:</strong> <span id="inspRejectionReason"></span>
                </div>

                <div id="inspBalanceWarning" class="alert alert-danger d-none">
                    <i class="ti tabler-alert-circle me-1"></i>
                    <strong><?= htmlspecialchars($language::get('billing_reseller_insufficient_warning')); ?></strong>
                </div>
            </div>
            <div class="modal-footer border-top d-flex justify-content-between">
                <div id="inspPendingActions" class="d-none w-100 d-flex justify-content-between">
                    <button type="button" class="btn btn-danger" id="btnInspReject">
                        <i class="ti tabler-x me-1"></i> <?= htmlspecialchars($language::get('billing_reseller_reject_btn')); ?>
                    </button>
                    <button type="button" class="btn btn-success" id="btnInspApprove">
                        <i class="ti tabler-check me-1"></i> <?= htmlspecialchars($language::get('billing_reseller_approve_btn')); ?>
                    </button>
                </div>
                <div id="inspClosedStatus" class="w-100 d-flex justify-content-between">
                    <span id="inspStatusBadge" class="fs-6"></span>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= htmlspecialchars($language::get('billing_common_close')); ?></button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
LayoutRenderer::renderFooter('reseller');
include __DIR__ . '/orders_scripts.php';
?>
