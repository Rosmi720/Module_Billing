<?php
use XcVm\Core\Util\LayoutRenderer;
use XcVm\Core\Localization\Translator;
use XcVm\Module\Billing\BillingService;

/** @var array $stats */
/** @var array $orders */
/** @var array $parentMethods */
global $rUserInfo, $rPermissions;
$userId = intval($userId ?? ($rUserInfo['id'] ?? 0));
$currentUser = !empty($currentUser) ? $currentUser : ($rUserInfo ?? []);
$language = Translator::class;
?>
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="ti tabler-credit-card me-2"></i><?= htmlspecialchars($language::get('billing_subreseller_wallet_title')); ?></h4>
            <p class="text-muted mb-0"><?= htmlspecialchars($language::get('billing_subreseller_wallet_desc')); ?></p>
        </div>
        <a href="billing_buy" class="btn btn-primary btn-lg">
            <i class="ti tabler-coin me-1"></i> <?= htmlspecialchars($language::get('billing_subreseller_buy_points_now')); ?>
        </a>
    </div>

    <!-- Stat Tiles -->
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm bg-label-primary">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted d-block mb-1"><?= htmlspecialchars($language::get('billing_reseller_your_balance')); ?></span>
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
                        <span class="text-muted d-block mb-1"><?= htmlspecialchars($language::get('billing_subreseller_total_purchased')); ?></span>
                        <h3 class="mb-0 fw-bold text-success"><?= number_format(floatval($stats['total_points_bought'] ?? 0), 2); ?></h3>
                    </div>
                    <div class="avatar avatar-lg bg-label-success rounded-circle p-2 d-flex align-items-center justify-content-center">
                        <i class="ti tabler-shopping-cart-check fs-2"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted d-block mb-1"><?= htmlspecialchars($language::get('billing_reseller_pending_orders')); ?></span>
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
                        <span class="text-muted d-block mb-1"><?= htmlspecialchars($language::get('billing_subreseller_completed_orders')); ?></span>
                        <h3 class="mb-0 fw-bold text-info"><?= intval($stats['approved_orders'] ?? 0); ?></h3>
                    </div>
                    <div class="avatar avatar-lg bg-label-info rounded-circle p-2 d-flex align-items-center justify-content-center">
                        <i class="ti tabler-circle-check fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Purchase Action Banner -->
    <div class="card border-0 shadow-sm bg-primary text-white mb-4">
        <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h4 class="text-white mb-1"><i class="ti tabler-bolt me-2"></i><?= htmlspecialchars($language::get('billing_subreseller_need_more_title')); ?></h4>
                <p class="text-white-50 mb-0"><?= htmlspecialchars($language::get('billing_subreseller_need_more_desc')); ?></p>
            </div>
            <a href="billing_buy" class="btn btn-warning btn-lg fw-bold px-4">
                <i class="ti tabler-plus me-1"></i> <?= htmlspecialchars($language::get('billing_subreseller_buy_points_now')); ?>
            </a>
        </div>
    </div>

    <!-- Recent Orders Table -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header d-flex justify-content-between align-items-center bg-transparent border-bottom">
            <h5 class="card-title mb-0"><i class="ti tabler-history me-2"></i><?= htmlspecialchars($language::get('billing_subreseller_recent_purchases')); ?></h5>
            <a href="billing_my_orders" class="btn btn-sm btn-outline-primary"><?= htmlspecialchars($language::get('billing_subreseller_view_full_history')); ?></a>
        </div>
        <div class="card-datatable table-responsive">
            <table class="table table-hover border-top align-middle mb-0 nowrap" id="subRecentOrdersTable">
                <thead class="table-light">
                    <tr>
                        <th><?= htmlspecialchars($language::get('billing_common_order_code')); ?></th>
                        <th><?= htmlspecialchars($language::get('billing_reseller_points_requested')); ?></th>
                        <th><?= htmlspecialchars($language::get('billing_reseller_total_cost')); ?></th>
                        <th><?= htmlspecialchars($language::get('billing_common_payment_method')); ?></th>
                        <th><?= htmlspecialchars($language::get('billing_common_reference')); ?></th>
                        <th><?= htmlspecialchars($language::get('billing_common_status')); ?></th>
                        <th><?= htmlspecialchars($language::get('billing_common_date')); ?></th>
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
                                        <span class="badge bg-label-success"><i class="ti tabler-check me-1"></i><?= htmlspecialchars($language::get('billing_common_status_approved')); ?></span>
                                    <?php elseif ($o['status'] === 'pending'): ?>
                                        <span class="badge bg-label-warning"><i class="ti tabler-clock me-1"></i><?= htmlspecialchars($language::get('billing_common_status_under_review')); ?></span>
                                    <?php elseif ($o['status'] === 'rejected'): ?>
                                        <span class="badge bg-label-danger"><i class="ti tabler-x me-1"></i><?= htmlspecialchars($language::get('billing_common_status_rejected')); ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-label-secondary"><?= htmlspecialchars((string) $o['status']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted">
                                    <?= date('Y-m-d H:i', intval($o['created_at'])); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php
    $canAddMethods = !empty($canManageSubResellers) || BillingService::canManageSubResellers($userId, $currentUser, $rPermissions);
    if ($canAddMethods):
        $myMethods = BillingService::getPaymentMethods($userId);
    ?>
    <!-- Configured Payment Methods for Sub-Resellers with Distributor Privileges -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header d-flex justify-content-between align-items-center bg-transparent border-bottom">
            <h5 class="card-title mb-0"><i class="ti tabler-wallet me-2"></i><?= htmlspecialchars($language::get('billing_reseller_configured_methods')); ?></h5>
            <a href="billing_methods" class="btn btn-sm btn-outline-primary"><i class="ti tabler-plus me-1"></i><?= htmlspecialchars($language::get('billing_reseller_add_edit_methods')); ?></a>
        </div>
        <div class="card-body p-4">
            <?php if (empty($myMethods)): ?>
                <div class="alert alert-warning mb-0 d-flex align-items-center">
                    <i class="ti tabler-alert-triangle fs-2 me-3"></i>
                    <div>
                        <h6 class="mb-1"><?= htmlspecialchars($language::get('billing_reseller_no_methods_title')); ?></h6>
                        <p class="mb-0 small"><?= htmlspecialchars($language::get('billing_reseller_no_methods_desc')); ?></p>
                        <a href="billing_methods" class="btn btn-sm btn-warning mt-2"><?= htmlspecialchars($language::get('billing_reseller_configure_methods_now')); ?></a>
                    </div>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($myMethods as $m): ?>
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
                                        <?= ($m['active'] == 1) ? htmlspecialchars($language::get('billing_common_active')) : htmlspecialchars($language::get('billing_common_disabled')); ?>
                                    </span>
                                </div>
                                <div class="small text-muted mb-2">
                                    <?= htmlspecialchars($language::get('billing_common_rate')); ?>: <strong><?= number_format(floatval($m['rate_per_point']), 2); ?> <?= htmlspecialchars((string) $m['currency']); ?></strong> / credit
                                </div>
                                <div class="small text-muted">
                                    <?= htmlspecialchars($language::get('billing_reseller_limits')); ?>: <?= intval($m['min_points']); ?> to <?= (intval($m['max_points']) > 0) ? intval($m['max_points']) : '∞'; ?> pts
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
LayoutRenderer::renderFooter('reseller');
include __DIR__ . '/overview_scripts.php';
?>
