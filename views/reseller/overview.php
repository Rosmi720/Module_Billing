<?php
use XcVm\Core\Util\LayoutRenderer;

/** @var array $stats */
/** @var array $pendingOrders */
/** @var array $methods */
global $rUserInfo;
?>
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="ti tabler-credit-card me-2"></i>Payments & Billing Hub</h4>
            <p class="text-muted mb-0">Manage your points wallet, sub-reseller purchase requests, and payment channels.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="billing_buy" class="btn btn-success">
                <i class="ti tabler-plus me-1"></i> Top Up Balance from Admin
            </a>
            <a href="billing_methods" class="btn btn-primary">
                <i class="ti tabler-wallet me-1"></i> Manage Methods
            </a>
            <a href="billing_orders" class="btn btn-outline-secondary">
                <i class="ti tabler-list-check me-1"></i> Sub-Reseller Orders
            </a>
        </div>
    </div>

    <!-- Stat Tiles -->
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm bg-label-primary">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted d-block mb-1">Your Points Balance</span>
                        <h3 class="mb-0 fw-bold text-primary"><?= number_format(floatval($stats['current_credits'] ?? 0), 2); ?></h3>
                    </div>
                    <div class="avatar avatar-lg bg-primary text-white rounded-circle p-2 d-flex align-items-center justify-content-center">
                        <i class="ti tabler-coin fs-2"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted d-block mb-1">Pending Orders</span>
                        <h3 class="mb-0 fw-bold <?= (!empty($stats['pending_orders'])) ? 'text-warning' : 'text-muted'; ?>">
                            <?= intval($stats['pending_orders'] ?? 0); ?>
                        </h3>
                    </div>
                    <div class="avatar avatar-lg bg-label-warning rounded-circle p-2 d-flex align-items-center justify-content-center">
                        <i class="ti tabler-clock-hour-4 fs-2"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted d-block mb-1">Total Points Sold</span>
                        <h3 class="mb-0 fw-bold text-success"><?= number_format(floatval($stats['total_points_approved'] ?? 0), 2); ?></h3>
                    </div>
                    <div class="avatar avatar-lg bg-label-success rounded-circle p-2 d-flex align-items-center justify-content-center">
                        <i class="ti tabler-arrows-transfer-up fs-2"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted d-block mb-1">Your Payment Methods</span>
                        <h3 class="mb-0 fw-bold text-info"><?= intval($stats['total_methods'] ?? 0); ?></h3>
                    </div>
                    <div class="avatar avatar-lg bg-label-info rounded-circle p-2 d-flex align-items-center justify-content-center">
                        <i class="ti tabler-wallet fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Purchase Requests Alert & Table -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header d-flex justify-content-between align-items-center bg-transparent border-bottom">
            <h5 class="card-title mb-0">
                <i class="ti tabler-bell-ringing me-2 text-warning"></i>Pending Sub-Reseller Purchase Requests
                <?php if (!empty($pendingOrders)): ?>
                    <span class="badge bg-warning ms-2"><?= count($pendingOrders); ?> Pending</span>
                <?php endif; ?>
            </h5>
            <a href="billing_orders" class="btn btn-sm btn-outline-primary">View All Orders</a>
        </div>
        <div class="card-datatable table-responsive">
            <table class="table table-hover border-top align-middle mb-0 nowrap" id="pendingOrdersTable">
                <thead class="table-light">
                    <tr>
                        <th>Order Code</th>
                        <th>Sub-Reseller</th>
                        <th>Points Requested</th>
                        <th>Total Cost</th>
                        <th>Method</th>
                        <th>Reference #</th>
                        <th>Date</th>
                        <th class="text-end no-sort">Review & Credit</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($pendingOrders)): ?>
                        <?php foreach ($pendingOrders as $order): ?>
                            <tr>
                                <td>
                                    <span class="fw-semibold text-primary font-monospace"><?= htmlspecialchars((string) $order['order_code']); ?></span>
                                </td>
                                <td>
                                    <span class="fw-bold text-dark"><?= htmlspecialchars((string) ($order['buyer_username'] ?: '#' . $order['user_id'])); ?></span>
                                </td>
                                <td>
                                    <span class="fw-bold text-success fs-6">+<?= number_format(floatval($order['points_requested']), 2); ?></span>
                                </td>
                                <td>
                                    <?= number_format(floatval($order['price_total']), 2); ?> <small class="text-muted"><?= htmlspecialchars((string) $order['currency']); ?></small>
                                </td>
                                <td>
                                    <i class="ti tabler-credit-card me-1 text-muted"></i><?= htmlspecialchars((string) $order['method_name']); ?>
                                </td>
                                <td>
                                    <span class="badge bg-label-info font-monospace"><?= htmlspecialchars((string) ($order['transaction_reference'] ?: '-')); ?></span>
                                </td>
                                <td class="small text-muted">
                                    <?= date('Y-m-d H:i', intval($order['created_at'])); ?>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-primary review-order-btn" data-id="<?= (int) $order['id']; ?>">
                                        <i class="ti tabler-eye-check me-1"></i> Review Request
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Configured Payment Methods Overview -->
    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center bg-transparent border-bottom">
            <h5 class="card-title mb-0"><i class="ti tabler-wallet me-2"></i>Your Configured Payment Methods</h5>
            <a href="billing_methods" class="btn btn-sm btn-outline-primary"><i class="ti tabler-plus me-1"></i>Add / Edit Methods</a>
        </div>
        <div class="card-body p-4">
            <?php if (empty($methods)): ?>
                <div class="alert alert-warning mb-0 d-flex align-items-center">
                    <i class="ti tabler-alert-triangle fs-2 me-3"></i>
                    <div>
                        <h6 class="mb-1">You haven't configured any payment methods yet!</h6>
                        <p class="mb-0 small">Your sub-resellers won't be able to buy points until you configure at least one active payment method.</p>
                        <a href="billing_methods" class="btn btn-sm btn-warning mt-2">Configure Payment Methods Now</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($methods as $m): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="border rounded p-3 h-100 <?= ($m['active'] == 1) ? 'bg-light' : 'opacity-75'; ?>">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-sm bg-label-primary rounded-circle me-2 d-flex align-items-center justify-content-center">
                                            <i class="<?= htmlspecialchars((string) ($m['icon'] ?: 'ti tabler-credit-card')); ?>"></i>
                                        </div>
                                        <h6 class="mb-0 fw-bold"><?= htmlspecialchars((string) $m['name']); ?></h6>
                                    </div>
                                    <span class="badge bg-label-<?= ($m['active'] == 1) ? 'success' : 'secondary'; ?>">
                                        <?= ($m['active'] == 1) ? 'Active' : 'Disabled'; ?>
                                    </span>
                                </div>
                                <div class="small text-muted mb-2">
                                    Rate: <strong><?= number_format(floatval($m['rate_per_point']), 2); ?> <?= htmlspecialchars((string) $m['currency']); ?></strong> / credit
                                </div>
                                <div class="small text-muted">
                                    Limits: <?= intval($m['min_points']); ?> to <?= (intval($m['max_points']) > 0) ? intval($m['max_points']) : '∞'; ?> pts
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal: Reseller Review Order & Credit Transfer -->
<div class="modal fade" id="resellerReviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title"><i class="ti tabler-clipboard-check me-2"></i>Review Purchase Order: <span id="resModalCode" class="text-primary font-monospace"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block">Sub-Reseller (Buyer)</small>
                            <span id="resModalBuyer" class="fw-bold fs-6"></span>
                            <div class="mt-2 small text-muted">Current Balance: <span id="resModalBuyerPoints" class="fw-semibold text-success"></span> credits</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-label-primary rounded">
                            <small class="text-muted d-block">Your Points Balance</small>
                            <span id="resModalMyPoints" class="fw-bold fs-6 text-primary"></span> credits
                            <div class="mt-2 small text-muted">Points to Transfer: <span id="resModalPointsReq" class="fw-bold text-success"></span> credits</div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <small class="text-muted d-block">Payment Method</small>
                        <span id="resModalMethod" class="fw-semibold"></span>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Total Amount Transferred</small>
                        <span id="resModalCost" class="fw-semibold text-success fs-6"></span>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Transaction / Reference #</small>
                        <span id="resModalTxRef" class="badge bg-label-info font-monospace fs-6"></span>
                    </div>
                </div>

                <div class="mb-4">
                    <small class="text-muted d-block mb-1">Buyer Notes</small>
                    <div id="resModalNotes" class="p-2 border rounded bg-white small text-break"></div>
                </div>

                <div class="mb-4" id="resModalReceiptWrap">
                    <small class="text-muted d-block mb-2"><i class="ti tabler-photo me-1"></i>Attached Transfer Receipt</small>
                    <div class="text-center p-3 border rounded bg-light">
                        <a id="resModalReceiptLink" href="#" target="_blank" class="d-inline-block">
                            <img id="resModalReceiptImg" src="" alt="Receipt" class="img-fluid rounded shadow-sm" style="max-height: 380px;">
                        </a>
                        <div class="mt-2">
                            <a id="resModalReceiptBtn" href="#" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="ti tabler-external-link me-1"></i>Open Full Receipt in New Tab
                            </a>
                        </div>
                    </div>
                </div>

                <div id="resBalanceWarning" class="alert alert-danger d-none">
                    <i class="ti tabler-alert-circle me-1"></i>
                    <strong>Insufficient Credits:</strong> You do not have enough credits in your wallet to approve this order! Please recharge your account first.
                </div>
            </div>
            <div class="modal-footer border-top d-flex justify-content-between">
                <button type="button" class="btn btn-danger" id="btnResReject">
                    <i class="ti tabler-x me-1"></i> Reject Request
                </button>
                <button type="button" class="btn btn-success" id="btnResApprove">
                    <i class="ti tabler-check me-1"></i> Approve & Transfer Points
                </button>
            </div>
        </div>
    </div>
</div>

<?php
LayoutRenderer::renderFooter('reseller');
include __DIR__ . '/overview_scripts.php';
?>
