<?php
use XcVm\Core\Util\LayoutRenderer;

/** @var array $orders */
/** @var int $totalCount */
?>
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="ti tabler-receipt me-2"></i>Master Orders Log</h4>
            <p class="text-muted mb-0">Review, audit, and manage all point purchase requests submitted across the system.</p>
        </div>
        <a href="billing" class="btn btn-outline-secondary">
            <i class="ti tabler-arrow-left me-1"></i> Back to Billing Overview
        </a>
    </div>

    <!-- Filter Toolbar -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="billing_orders" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text"><i class="ti tabler-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search by Order Code, Username, or Ref..." value="<?= htmlspecialchars((string) ($_GET['search'] ?? '')); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="pending" <?= (($_GET['status'] ?? '') === 'pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?= (($_GET['status'] ?? '') === 'approved') ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?= (($_GET['status'] ?? '') === 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100"><i class="ti tabler-filter me-1"></i>Apply Filters</button>
                </div>
                <div class="col-md-2">
                    <a href="billing_orders" class="btn btn-outline-secondary w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-datatable table-responsive">
            <table class="table table-hover align-middle mb-0" id="billingOrdersTable" style="width:100%">
                <thead class="table-light">
                    <tr>
                        <th>Order Code</th>
                        <th>Buyer (Sub-Reseller)</th>
                        <th>Seller (Reseller)</th>
                        <th>Points</th>
                        <th>Cost</th>
                        <th>Method</th>
                        <th>Reference #</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th class="text-end no-sort">Actions</th>
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
                                    <span class="badge bg-label-dark"><?= htmlspecialchars((string) ($o['seller_username'] ?: ($o['seller_id'] == 0 ? 'Admin' : '#' . $o['seller_id']))); ?></span>
                                </td>
                                <td>
                                    <span class="fw-bold text-success">+<?= number_format(floatval($o['points_requested']), 2); ?></span>
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
                                        <span class="badge bg-label-success"><i class="ti tabler-check me-1"></i>Approved</span>
                                    <?php elseif ($o['status'] === 'pending'): ?>
                                        <span class="badge bg-label-warning"><i class="ti tabler-clock me-1"></i>Pending</span>
                                    <?php elseif ($o['status'] === 'rejected'): ?>
                                        <span class="badge bg-label-danger"><i class="ti tabler-x me-1"></i>Rejected</span>
                                    <?php else: ?>
                                        <span class="badge bg-label-secondary"><?= htmlspecialchars((string) $o['status']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted">
                                    <?= date('Y-m-d H:i', intval($o['created_at'])); ?>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-label-primary view-admin-order-btn" data-id="<?= (int) $o['id']; ?>">
                                        <i class="ti tabler-eye me-1"></i> Inspect
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

<!-- Modal: Admin Review & Override Action -->
<div class="modal fade" id="adminOrderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title"><i class="ti tabler-clipboard-check me-2"></i>Inspect Order: <span id="admModalCode" class="text-primary"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block">Buyer (Sub-Reseller)</small>
                            <span id="admModalBuyer" class="fw-bold fs-6"></span>
                            <div class="mt-2 small text-muted">Current Balance: <span id="admModalBuyerPoints" class="fw-semibold text-success"></span> credits</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block">Designated Seller</small>
                            <span id="admModalSeller" class="fw-bold fs-6"></span>
                            <div class="mt-2 small text-muted">Points Requested: <span id="admModalPoints" class="fw-bold text-primary"></span> credits</div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <small class="text-muted d-block">Payment Method</small>
                        <span id="admModalMethod" class="fw-semibold"></span>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Total Cost</small>
                        <span id="admModalCost" class="fw-semibold text-success"></span>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Transaction / Reference #</small>
                        <span id="admModalTxRef" class="badge bg-label-info font-monospace fs-6"></span>
                    </div>
                </div>

                <div class="mb-4">
                    <small class="text-muted d-block mb-1">Sender Notes</small>
                    <div id="admModalNotes" class="p-2 border rounded bg-white small text-break"></div>
                </div>

                <div class="mb-4" id="admModalReceiptWrap">
                    <small class="text-muted d-block mb-2"><i class="ti tabler-photo me-1"></i>Uploaded Payment Receipt / Slip</small>
                    <div class="text-center p-3 border rounded bg-light">
                        <a id="admModalReceiptLink" href="#" target="_blank" class="d-inline-block">
                            <img id="admModalReceiptImg" src="" alt="Receipt" class="img-fluid rounded shadow-sm" style="max-height: 350px;">
                        </a>
                        <div class="mt-2">
                            <a id="admModalReceiptBtn" href="#" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="ti tabler-external-link me-1"></i>Open Full Receipt in New Tab
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Admin Action Section for Pending Orders -->
                <div id="admActionSection" class="p-3 border rounded bg-label-secondary d-none">
                    <h6 class="mb-2"><i class="ti tabler-shield-check me-1"></i>Admin Override Action</h6>
                    <p class="small text-muted mb-3">As system administrator, you can directly approve and credit this order or reject it.</p>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-success" id="btnAdminApprove">
                            <i class="ti tabler-check me-1"></i> Approve & Credit Directly
                        </button>
                        <button type="button" class="btn btn-danger" id="btnAdminReject">
                            <i class="ti tabler-x me-1"></i> Reject Order
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top d-flex justify-content-between">
                <span id="admModalStatus"></span>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php
LayoutRenderer::renderFooter('admin');
include __DIR__ . '/orders_scripts.php';
?>
