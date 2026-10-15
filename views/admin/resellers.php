<?php
use XcVm\Core\Util\LayoutRenderer;
use XcVm\Core\Localization\Translator;

/** @var array $resellers */
$language = Translator::class;

$totalResellers = count($resellers);
$rootResellers = 0;
$subResellers = 0;
$totalCirculatingCredits = 0.0;
$uniqueGroups = [];

foreach ($resellers as $r) {
    if (empty($r['owner_id'])) {
        $rootResellers++;
    } else {
        $subResellers++;
    }
    $totalCirculatingCredits += floatval($r['credits'] ?? 0);
    if (!empty($r['group_name'])) {
        $uniqueGroups[$r['group_name']] = true;
    }
}
?>
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="ti tabler-users me-2"></i><?= htmlspecialchars($language::get('billing_admin_resellers_title')); ?></h4>
            <p class="text-muted mb-0"><?= htmlspecialchars($language::get('billing_admin_resellers_desc')); ?></p>
        </div>
        <div class="d-flex gap-2">
            <a href="users" class="btn btn-outline-primary">
                <i class="ti tabler-user-cog me-1"></i> Manage Users
            </a>
            <a href="billing" class="btn btn-outline-secondary">
                <i class="ti tabler-arrow-left me-1"></i> <?= htmlspecialchars($language::get('billing_common_back_billing')); ?>
            </a>
        </div>
    </div>

    <!-- Quick Metrics Strip -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-md bg-label-primary rounded me-3 d-flex align-items-center justify-content-center">
                        <i class="ti tabler-users fs-3"></i>
                    </div>
                    <div>
                        <span class="text-muted small d-block">Total Resellers</span>
                        <h4 class="fw-bold mb-0 text-dark"><?= number_format($totalResellers); ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-md bg-label-success rounded me-3 d-flex align-items-center justify-content-center">
                        <i class="ti tabler-crown fs-3"></i>
                    </div>
                    <div>
                        <span class="text-muted small d-block">Direct Master Distributors</span>
                        <h4 class="fw-bold mb-0 text-dark"><?= number_format($rootResellers); ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-md bg-label-info rounded me-3 d-flex align-items-center justify-content-center">
                        <i class="ti tabler-git-branch fs-3"></i>
                    </div>
                    <div>
                        <span class="text-muted small d-block">Sub-Resellers</span>
                        <h4 class="fw-bold mb-0 text-dark"><?= number_format($subResellers); ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-md bg-label-warning rounded me-3 d-flex align-items-center justify-content-center">
                        <i class="ti tabler-wallet fs-3"></i>
                    </div>
                    <div>
                        <span class="text-muted small d-block">Total Circulating Points</span>
                        <h4 class="fw-bold mb-0 text-warning"><?= number_format($totalCirculatingCredits, 2); ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Reseller Directory Card -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
            <h5 class="card-title mb-0">
                <i class="ti tabler-list me-2"></i><?= htmlspecialchars($language::get('billing_admin_resellers_title')); ?>
            </h5>
            <div class="d-flex align-items-center gap-2">
                <select class="form-select form-select-sm" id="filterResellerLevel" style="min-width: 170px;">
                    <option value="">All Distribution Levels</option>
                    <option value="Direct Master">Direct Master</option>
                    <option value="Sub-Reseller">Sub-Reseller</option>
                </select>
                <?php if (count($uniqueGroups) > 1): ?>
                    <select class="form-select form-select-sm" id="filterResellerGroup" style="min-width: 140px;">
                        <option value="">All Groups</option>
                        <?php foreach (array_keys($uniqueGroups) as $grp): ?>
                            <option value="<?= htmlspecialchars($grp); ?>"><?= htmlspecialchars($grp); ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>
        </div>

        <div class="card-datatable table-responsive p-3">
            <table class="table table-hover align-middle mb-0" id="resellersTable" style="width:100%">
                <thead class="table-light">
                    <tr>
                        <th style="width: 60px;">ID</th>
                        <th><?= htmlspecialchars($language::get('billing_admin_reseller_username')); ?></th>
                        <th>Level / Parent</th>
                        <th><?= htmlspecialchars($language::get('billing_admin_reseller_group')); ?></th>
                        <th><?= htmlspecialchars($language::get('billing_admin_reseller_credits')); ?></th>
                        <th class="text-center">Status</th>
                        <th class="text-center"><?= htmlspecialchars($language::get('billing_admin_reseller_sub_count')); ?></th>
                        <th class="text-center">Lines</th>
                        <th class="text-center"><?= htmlspecialchars($language::get('billing_admin_reseller_methods_count')); ?></th>
                        <th><?= htmlspecialchars($language::get('billing_reseller_total_sold')); ?></th>
                        <th class="text-end no-sort"><?= htmlspecialchars($language::get('billing_admin_resellers_drilldown')); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($resellers)): ?>
                        <?php foreach ($resellers as $res): ?>
                            <tr>
                                <td class="font-monospace text-muted">#<?= (int) $res['id']; ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-sm bg-label-primary rounded-circle me-2 d-flex align-items-center justify-content-center">
                                            <i class="ti tabler-user"></i>
                                        </div>
                                        <div>
                                            <span class="fw-bold d-block text-dark"><?= htmlspecialchars((string) $res['username']); ?></span>
                                            <small class="text-muted"><?= htmlspecialchars((string) ($res['email'] ?: 'No email configured')); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if (empty($res['owner_id'])): ?>
                                        <span class="badge bg-label-primary">
                                            <i class="ti tabler-crown me-1"></i>Direct Master
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-label-info">
                                            <i class="ti tabler-corner-down-right me-1"></i>Sub-Reseller (<?= htmlspecialchars($res['parent_username'] ?: '#' . $res['owner_id']); ?>)
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-label-dark"><?= htmlspecialchars((string) $res['group_name']); ?></span>
                                </td>
                                <td>
                                    <span class="fw-bold text-success fs-6"><?= number_format(floatval($res['credits']), 2); ?></span>
                                </td>
                                <td class="text-center">
                                    <?php if (!empty($res['status'])): ?>
                                        <span class="badge bg-label-success"><i class="ti tabler-check me-1"></i>Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-label-danger"><i class="ti tabler-x me-1"></i>Suspended</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-label-secondary"><i class="ti tabler-users me-1"></i><?= intval($res['sub_reseller_count']); ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-label-info"><i class="ti tabler-device-tv me-1"></i><?= intval($res['lines_count']); ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-label-primary"><i class="ti tabler-wallet me-1"></i><?= intval($res['methods_count']); ?></span>
                                </td>
                                <td>
                                    <span class="fw-semibold text-primary"><?= number_format(floatval($res['total_points_sold'] ?? 0), 2); ?> pts</span>
                                    <small class="text-muted d-block"><?= intval($res['orders_count']); ?> orders</small>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <button type="button" class="btn btn-sm btn-primary drilldown-btn" data-id="<?= (int) $res['id']; ?>" data-name="<?= htmlspecialchars((string) $res['username'], ENT_QUOTES); ?>">
                                            <i class="ti tabler-zoom-in me-1"></i> <?= htmlspecialchars($language::get('billing_common_view')); ?>
                                        </button>
                                        <a href="user?id=<?= (int) $res['id']; ?>" class="btn btn-sm btn-label-secondary" title="Edit Reseller Account">
                                            <i class="ti tabler-edit"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal for Reseller Drill-Down -->
<div class="modal fade" id="drilldownModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <div>
                    <h5 class="modal-title mb-0">
                        <i class="ti tabler-chart-pie me-2"></i><?= htmlspecialchars($language::get('billing_admin_resellers_title')); ?>: <span id="drillResellerName" class="text-primary fw-bold"></span>
                    </h5>
                    <small class="text-muted">ID: #<span id="drillResellerId"></span> | <?= htmlspecialchars($language::get('billing_reseller_your_balance')); ?>: <span id="drillResellerBalance" class="text-success fw-bold"></span> credits</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Nav Tabs -->
                <ul class="nav nav-tabs nav-fill mb-4" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-drill-methods" role="tab">
                            <i class="ti tabler-wallet me-1"></i> <?= htmlspecialchars($language::get('billing_common_methods')); ?> (<span id="drillMethodsCount">0</span>)
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-drill-orders" role="tab">
                            <i class="ti tabler-receipt me-1"></i> <?= htmlspecialchars($language::get('billing_reseller_nav_orders')); ?> (<span id="drillOrdersCount">0</span>)
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-drill-logs" role="tab">
                            <i class="ti tabler-activity me-1"></i> Activity Logs
                        </button>
                    </li>
                </ul>

                <!-- Tab Panes -->
                <div class="tab-content">
                    <!-- Methods Tab -->
                    <div class="tab-pane fade show active" id="tab-drill-methods" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th><?= htmlspecialchars($language::get('billing_common_payment_method')); ?></th>
                                        <th>Slug</th>
                                        <th><?= htmlspecialchars($language::get('billing_common_rate')); ?></th>
                                        <th><?= htmlspecialchars($language::get('billing_reseller_limits')); ?></th>
                                        <th><?= htmlspecialchars($language::get('billing_common_status')); ?></th>
                                        <th><?= htmlspecialchars($language::get('billing_subreseller_transfer_instructions')); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="drillMethodsTableBody">
                                    <tr><td colspan="6" class="text-center py-3 text-muted"><?= htmlspecialchars($language::get('billing_common_processing')); ?></td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Orders Tab -->
                    <div class="tab-pane fade" id="tab-drill-orders" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
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
                                        <th><?= htmlspecialchars($language::get('billing_common_receipt')); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="drillOrdersTableBody">
                                    <tr><td colspan="9" class="text-center py-3 text-muted"><?= htmlspecialchars($language::get('billing_common_processing')); ?></td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Logs Tab -->
                    <div class="tab-pane fade" id="tab-drill-logs" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Action</th>
                                        <th>Target</th>
                                        <th>Details</th>
                                        <th>IP Address</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody id="drillLogsTableBody">
                                    <tr><td colspan="5" class="text-center py-3 text-muted">No activity logs recorded.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= htmlspecialchars($language::get('billing_common_close')); ?></button>
            </div>
        </div>
    </div>
</div>

<?php
LayoutRenderer::renderFooter('admin');
include __DIR__ . '/resellers_scripts.php';
?>
