<?php
use XcVm\Core\Util\LayoutRenderer;

/** @var array $resellers */
?>
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="ti tabler-users me-2"></i>Resellers Billing Directory</h4>
            <p class="text-muted mb-0">Detailed financial drill-down into every reseller: balances, configured payment methods, and sub-reseller operations.</p>
        </div>
        <a href="billing" class="btn btn-outline-secondary">
            <i class="ti tabler-arrow-left me-1"></i> Back to Billing Overview
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-bottom">
            <h5 class="card-title mb-0"><i class="ti tabler-list me-2"></i>All Registered Resellers</h5>
        </div>
        <div class="card-datatable table-responsive">
            <table class="table table-hover align-middle mb-0" id="resellersTable" style="width:100%">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Reseller</th>
                        <th>Group</th>
                        <th>Current Points</th>
                        <th>Sub-Resellers</th>
                        <th>Payment Methods</th>
                        <th>Total Orders</th>
                        <th>Points Sold</th>
                        <th class="text-end no-sort">Drill-Down</th>
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
                                    <span class="badge bg-label-primary"><i class="ti tabler-wallet me-1"></i><?= intval($res['methods_count']); ?> methods</span>
                                </td>
                                <td>
                                    <span class="badge bg-label-dark"><?= intval($res['orders_count']); ?> orders</span>
                                </td>
                                <td>
                                    <span class="fw-semibold text-primary"><?= number_format(floatval($res['total_points_sold'] ?? 0), 2); ?></span>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-primary drilldown-btn" data-id="<?= (int) $res['id']; ?>" data-name="<?= htmlspecialchars((string) $res['username'], ENT_QUOTES); ?>">
                                        <i class="ti tabler-zoom-in me-1"></i> View Details
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
                        <i class="ti tabler-chart-pie me-2"></i>Reseller Financial Profile: <span id="drillResellerName" class="text-primary fw-bold"></span>
                    </h5>
                    <small class="text-muted">ID: #<span id="drillResellerId"></span> | Current Balance: <span id="drillResellerBalance" class="text-success fw-bold"></span> credits</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Nav Tabs -->
                <ul class="nav nav-tabs nav-fill mb-4" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-drill-methods" role="tab">
                            <i class="ti tabler-wallet me-1"></i> Payment Methods (<span id="drillMethodsCount">0</span>)
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-drill-orders" role="tab">
                            <i class="ti tabler-receipt me-1"></i> Sub-Reseller Orders (<span id="drillOrdersCount">0</span>)
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
                                        <th>Name</th>
                                        <th>Slug</th>
                                        <th>Rate Per Point</th>
                                        <th>Min / Max Points</th>
                                        <th>Status</th>
                                        <th>Instructions</th>
                                    </tr>
                                </thead>
                                <tbody id="drillMethodsTableBody">
                                    <tr><td colspan="6" class="text-center py-3 text-muted">Loading...</td></tr>
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
                                        <th>Order Code</th>
                                        <th>Sub-Reseller</th>
                                        <th>Points</th>
                                        <th>Cost</th>
                                        <th>Method</th>
                                        <th>Reference #</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Receipt</th>
                                    </tr>
                                </thead>
                                <tbody id="drillOrdersTableBody">
                                    <tr><td colspan="9" class="text-center py-3 text-muted">Loading...</td></tr>
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
                                    <tr><td colspan="5" class="text-center py-3 text-muted">Loading...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php
LayoutRenderer::renderFooter('admin');
include __DIR__ . '/resellers_scripts.php';
?>
