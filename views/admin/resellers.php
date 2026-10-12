<?php
use XcVm\Core\Util\LayoutRenderer;
use XcVm\Core\Localization\Translator;

/** @var array $resellers */
$language = Translator::class;
?>
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="ti tabler-users me-2"></i><?= htmlspecialchars($language::get('billing_admin_resellers_title')); ?></h4>
            <p class="text-muted mb-0"><?= htmlspecialchars($language::get('billing_admin_resellers_desc')); ?></p>
        </div>
        <a href="billing" class="btn btn-outline-secondary">
            <i class="ti tabler-arrow-left me-1"></i> <?= htmlspecialchars($language::get('billing_common_back_billing')); ?>
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-bottom">
            <h5 class="card-title mb-0"><i class="ti tabler-list me-2"></i><?= htmlspecialchars($language::get('billing_admin_resellers_title')); ?></h5>
        </div>
        <div class="card-datatable table-responsive">
            <table class="table table-hover align-middle mb-0" id="resellersTable" style="width:100%">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th><?= htmlspecialchars($language::get('billing_admin_reseller_username')); ?></th>
                        <th><?= htmlspecialchars($language::get('billing_admin_reseller_group')); ?></th>
                        <th><?= htmlspecialchars($language::get('billing_admin_reseller_credits')); ?></th>
                        <th><?= htmlspecialchars($language::get('billing_admin_reseller_sub_count')); ?></th>
                        <th><?= htmlspecialchars($language::get('billing_admin_reseller_methods_count')); ?></th>
                        <th><?= htmlspecialchars($language::get('billing_admin_reseller_orders_count')); ?></th>
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
                                            <small class="text-muted"><?= htmlspecialchars((string) ($res['email'] ?: 'No email')); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-label-info"><?= htmlspecialchars((string) $res['group_name']); ?></span>
                                </td>
                                <td>
                                    <span class="fw-bold text-success fs-6"><?= number_format(floatval($res['credits']), 2); ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-label-secondary"><i class="ti tabler-users me-1"></i><?= intval($res['sub_reseller_count']); ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-label-primary"><i class="ti tabler-wallet me-1"></i><?= intval($res['methods_count']); ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-label-dark"><?= intval($res['orders_count']); ?></span>
                                </td>
                                <td>
                                    <span class="fw-semibold text-primary"><?= number_format(floatval($res['total_points_sold'] ?? 0), 2); ?></span>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-primary drilldown-btn" data-id="<?= (int) $res['id']; ?>" data-name="<?= htmlspecialchars((string) $res['username'], ENT_QUOTES); ?>">
                                        <i class="ti tabler-zoom-in me-1"></i> <?= htmlspecialchars($language::get('billing_common_view')); ?>
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

<!-- Offcanvas / Modal for Reseller Drill-Down -->
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
                            <i class="ti tabler-activity me-1"></i> Audit Logs
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
                                    <tr><td colspan="5" class="text-center py-3 text-muted"><?= htmlspecialchars($language::get('billing_common_processing')); ?></td></tr>
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
