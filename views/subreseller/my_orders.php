<?php
use XcVm\Core\Util\LayoutRenderer;

/** @var array $orders */
?>
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="ti tabler-history me-2"></i>My Points Purchase History</h4>
            <p class="text-muted mb-0">Track all your point recharge orders and distributor review statuses.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="billing_buy" class="btn btn-primary">
                <i class="ti tabler-coin me-1"></i> Buy Points
            </a>
            <a href="billing" class="btn btn-outline-secondary">
                <i class="ti tabler-arrow-left me-1"></i> Back to Wallet
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-bottom">
            <h5 class="card-title mb-0"><i class="ti tabler-receipt me-2"></i>All Purchase Orders</h5>
        </div>
        <div class="card-datatable table-responsive">
            <table class="table table-hover border-top align-middle mb-0 nowrap" id="myOrdersTable">
                <thead class="table-light">
                    <tr>
                        <th>Order Code</th>
                        <th>Points Requested</th>
                        <th>Total Cost</th>
                        <th>Method</th>
                        <th>Reference #</th>
                        <th>Status</th>
                        <th>Notes / Reason</th>
                        <th>Date</th>
                        <th class="text-end no-sort">Receipt</th>
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
                                        <span class="badge bg-label-success"><i class="ti tabler-check me-1"></i>Approved</span>
                                    <?php elseif ($o['status'] === 'pending'): ?>
                                        <span class="badge bg-label-warning"><i class="ti tabler-clock me-1"></i>Under Review</span>
                                    <?php elseif ($o['status'] === 'rejected'): ?>
                                        <span class="badge bg-label-danger"><i class="ti tabler-x me-1"></i>Rejected</span>
                                    <?php else: ?>
                                        <span class="badge bg-label-secondary"><?= htmlspecialchars((string) $o['status']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($o['rejection_reason'])): ?>
                                        <span class="text-danger small" title="<?= htmlspecialchars((string) $o['rejection_reason'], ENT_QUOTES); ?>">
                                            <i class="ti tabler-alert-triangle me-1"></i><?= htmlspecialchars(mb_strimwidth((string) $o['rejection_reason'], 0, 30, '...')); ?>
                                        </span>
                                    <?php elseif (!empty($o['sender_notes'])): ?>
                                        <span class="text-muted small"><?= htmlspecialchars(mb_strimwidth((string) $o['sender_notes'], 0, 30, '...')); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted">
                                    <?= date('Y-m-d H:i', intval($o['created_at'])); ?>
                                </td>
                                <td class="text-end">
                                    <?php if (!empty($o['receipt_image'])): ?>
                                        <a href="billing_receipt?id=<?= (int) $o['id']; ?>" target="_blank" class="btn btn-sm btn-label-primary" title="View Uploaded Receipt">
                                            <i class="ti tabler-photo me-1"></i> View Receipt
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
LayoutRenderer::renderFooter('reseller');
include __DIR__ . '/my_orders_scripts.php';
?>
