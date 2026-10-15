<?php
use XcVm\Core\Util\LayoutRenderer;
use XcVm\Core\Localization\Translator;

/** @var array $overview */
/** @var array $gateways */
/** @var array $topPackages */
/** @var array $ledger */
/** @var int $totalLedgerCount */
/** @var string|null $currency */

$language = Translator::class;
$revenueByCurrency = $overview['revenue_by_currency'] ?? [];
$pendingByCurrency = $overview['pending_by_currency'] ?? [];
?>

<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Header & Action Toolbar -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="ti tabler-chart-pie me-2 text-primary"></i><?= htmlspecialchars($language::get('billing_finance_admin_title')); ?>
            </h4>
            <p class="text-muted mb-0"><?= htmlspecialchars($language::get('billing_finance_admin_desc')); ?></p>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <!-- Currency Filter Dropdown -->
            <div class="btn-group">
                <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" id="currencyFilterBtn">
                    <i class="ti tabler-currency-dollar me-1"></i>
                    <span id="currentCurrencyLabel"><?= !empty($currency) ? htmlspecialchars($currency) : htmlspecialchars($language::get('billing_finance_filter_all_currencies')); ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm" id="currencyDropdownMenu">
                    <li><a class="dropdown-item active" href="javascript:void(0);" data-currency=""><?= htmlspecialchars($language::get('billing_finance_filter_all_currencies')); ?></a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="javascript:void(0);" data-currency="USD">USD ($)</a></li>
                    <li><a class="dropdown-item" href="javascript:void(0);" data-currency="EUR">EUR (€)</a></li>
                    <li><a class="dropdown-item" href="javascript:void(0);" data-currency="EGP">EGP (ج.م)</a></li>
                    <li><a class="dropdown-item" href="javascript:void(0);" data-currency="SAR">SAR (ر.س)</a></li>
                    <li><a class="dropdown-item" href="javascript:void(0);" data-currency="GBP">GBP (£)</a></li>
                </ul>
            </div>

            <!-- Date Range Selector -->
            <div class="btn-group">
                <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" id="dateFilterBtn">
                    <i class="ti tabler-calendar me-1"></i>
                    <span id="currentDateRangeLabel"><?= htmlspecialchars($language::get('billing_finance_filter_all_time')); ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm" id="dateRangeDropdownMenu">
                    <li><a class="dropdown-item" href="javascript:void(0);" data-range="today"><?= htmlspecialchars($language::get('billing_finance_filter_today')); ?></a></li>
                    <li><a class="dropdown-item" href="javascript:void(0);" data-range="yesterday"><?= htmlspecialchars($language::get('billing_finance_filter_yesterday')); ?></a></li>
                    <li><a class="dropdown-item" href="javascript:void(0);" data-range="7days"><?= htmlspecialchars($language::get('billing_finance_filter_7days')); ?></a></li>
                    <li><a class="dropdown-item" href="javascript:void(0);" data-range="30days"><?= htmlspecialchars($language::get('billing_finance_filter_30days')); ?></a></li>
                    <li><a class="dropdown-item" href="javascript:void(0);" data-range="this_month"><?= htmlspecialchars($language::get('billing_finance_filter_this_month')); ?></a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item active" href="javascript:void(0);" data-range="all"><?= htmlspecialchars($language::get('billing_finance_filter_all_time')); ?></a></li>
                </ul>
            </div>

            <!-- Export Button -->
            <a href="api?action=billing_finance_export" class="btn btn-success shadow-sm" target="_blank" id="btnExportLedger">
                <i class="ti tabler-file-spreadsheet me-1"></i><?= htmlspecialchars($language::get('billing_finance_ledger_export_csv')); ?>
            </a>
        </div>
    </div>

    <!-- Executive KPI Cards -->
    <div class="row g-4 mb-4" id="financeKpiRow">
        <!-- 1. Realized Gross Revenue -->
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted fw-semibold"><?= htmlspecialchars($language::get('billing_finance_kpi_gross_revenue')); ?></span>
                        <div class="avatar avatar-md bg-label-success rounded-circle d-flex align-items-center justify-content-center">
                            <i class="ti tabler-cash fs-3"></i>
                        </div>
                    </div>
                    <div id="kpiRevenueContainer">
                        <?php if (!empty($revenueByCurrency)): ?>
                            <?php foreach ($revenueByCurrency as $curr => $data): ?>
                                <h3 class="mb-0 fw-bold text-success d-inline-block me-2">
                                    <?= number_format($data['revenue'], 2); ?> <small class="fs-6"><?= htmlspecialchars($curr); ?></small>
                                </h3>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <h3 class="mb-0 fw-bold text-success">0.00 <small class="fs-6">USD</small></h3>
                        <?php endif; ?>
                    </div>
                    <small class="text-muted d-block mt-2">
                        <i class="ti tabler-circle-check text-success me-1"></i>
                        <span id="kpiApprovedOrdersCount"><?= intval($overview['approved_orders_count'] ?? 0); ?></span> <?= htmlspecialchars($language::get('billing_common_orders')); ?>
                    </small>
                </div>
            </div>
        </div>

        <!-- 2. Points Minted vs Consumed -->
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted fw-semibold"><?= htmlspecialchars($language::get('billing_finance_kpi_points_consumed')); ?></span>
                        <div class="avatar avatar-md bg-label-primary rounded-circle d-flex align-items-center justify-content-center">
                            <i class="ti tabler-flame fs-3"></i>
                        </div>
                    </div>
                    <h3 class="mb-0 fw-bold text-primary" id="kpiPointsConsumed">
                        <?= number_format($overview['total_points_consumed'] ?? 0, 2); ?>
                    </h3>
                    <div class="d-flex justify-content-between align-items-center mt-2 small text-muted">
                        <span><?= htmlspecialchars($language::get('billing_finance_chart_points_issued')); ?>:</span>
                        <strong class="text-dark" id="kpiPointsMinted"><?= number_format($overview['total_points_minted'] ?? 0, 2); ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Unspent Circulating Points Liability -->
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted fw-semibold"><?= htmlspecialchars($language::get('billing_finance_kpi_circulating_liability')); ?></span>
                        <div class="avatar avatar-md bg-label-warning rounded-circle d-flex align-items-center justify-content-center">
                            <i class="ti tabler-wallet fs-3"></i>
                        </div>
                    </div>
                    <h3 class="mb-0 fw-bold text-warning" id="kpiCirculatingLiability">
                        <?= number_format($overview['circulating_liability'] ?? 0, 2); ?> <small class="fs-6"><?= htmlspecialchars($language::get('billing_common_points')); ?></small>
                    </h3>
                    <small class="text-muted d-block mt-2">
                        <i class="ti tabler-users text-warning me-1"></i>
                        <span id="kpiActiveWallets"><?= intval($overview['active_wallets_count'] ?? 0); ?></span> <?= htmlspecialchars($language::get('billing_finance_kpi_active_wallets')); ?>
                    </small>
                </div>
            </div>
        </div>

        <!-- 4. Pending Receivables Flow -->
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted fw-semibold"><?= htmlspecialchars($language::get('billing_finance_kpi_pending_flow')); ?></span>
                        <div class="avatar avatar-md bg-label-info rounded-circle d-flex align-items-center justify-content-center">
                            <i class="ti tabler-clock-hour-4 fs-3"></i>
                        </div>
                    </div>
                    <div id="kpiPendingContainer">
                        <?php if (!empty($pendingByCurrency)): ?>
                            <?php foreach ($pendingByCurrency as $curr => $data): ?>
                                <h3 class="mb-0 fw-bold text-info d-inline-block me-2">
                                    <?= number_format($data['amount'], 2); ?> <small class="fs-6"><?= htmlspecialchars($curr); ?></small>
                                </h3>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <h3 class="mb-0 fw-bold text-muted">0.00 <small class="fs-6">USD</small></h3>
                        <?php endif; ?>
                    </div>
                    <small class="text-muted d-block mt-2">
                        <i class="ti tabler-alert-circle text-info me-1"></i>
                        <span id="kpiPendingCount"><?= intval($overview['pending_orders_count'] ?? 0); ?></span> <?= htmlspecialchars($language::get('billing_common_status_pending')); ?>
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts & Analytics Section -->
    <div class="row g-4 mb-4">
        <!-- Main Timeline Chart (Revenue & Point Velocity) -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center bg-transparent border-bottom">
                    <div>
                        <h5 class="card-title mb-1">
                            <i class="ti tabler-chart-line me-2 text-primary"></i><?= htmlspecialchars($language::get('billing_finance_chart_title')); ?>
                        </h5>
                        <small class="text-muted">Daily trend of incoming fiat revenue vs points consumption</small>
                    </div>
                    <div class="btn-group btn-group-sm" role="group" id="chartRangeButtons">
                        <button type="button" class="btn btn-outline-primary" data-days="7">7D</button>
                        <button type="button" class="btn btn-outline-primary active" data-days="30">30D</button>
                        <button type="button" class="btn btn-outline-primary" data-days="90">90D</button>
                    </div>
                </div>
                <div class="card-body position-relative">
                    <div id="financeChartSpinner" class="d-flex align-items-center justify-content-center h-100 py-5 d-none">
                        <div class="spinner-border text-primary" role="status"></div>
                    </div>
                    <div id="financeTimelineChart" style="min-height: 330px;"></div>
                </div>
            </div>
        </div>

        <!-- Breakdown Side Column (Gateways & Top Packages) -->
        <div class="col-lg-4">
            <div class="row g-4">
                <!-- Gateway Share Donut -->
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent border-bottom">
                            <h6 class="card-title mb-0">
                                <i class="ti tabler-wallet me-2 text-info"></i><?= htmlspecialchars($language::get('billing_finance_gateways_title')); ?>
                            </h6>
                        </div>
                        <div class="card-body p-3">
                            <div id="gatewaysDonutChart" style="min-height: 190px;"></div>
                            <div id="gatewaysList" class="mt-2">
                                <?php if (!empty($gateways)): ?>
                                    <div class="list-group list-group-flush small">
                                        <?php foreach (array_slice($gateways, 0, 4) as $gw): ?>
                                            <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-1 border-0">
                                                <span class="text-truncate" style="max-width: 140px;">
                                                    <i class="ti tabler-circle-filled fs-6 me-1 text-primary"></i>
                                                    <?= htmlspecialchars($gw['method_name'] ?? 'Gateway'); ?>
                                                </span>
                                                <span class="fw-bold text-dark">
                                                    <?= number_format(floatval($gw['total_amount']), 2); ?> <?= htmlspecialchars($gw['currency']); ?>
                                                </span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Packages Consuming Points -->
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent border-bottom">
                            <h6 class="card-title mb-0">
                                <i class="ti tabler-package me-2 text-success"></i><?= htmlspecialchars($language::get('billing_finance_top_packages_title')); ?>
                            </h6>
                        </div>
                        <div class="card-body p-3">
                            <div id="topPackagesList">
                                <?php if (!empty($topPackages)): ?>
                                    <ul class="list-group list-group-flush small">
                                        <?php foreach ($topPackages as $pkg): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                                <div>
                                                    <span class="fw-semibold text-dark d-block"><?= htmlspecialchars($pkg['package_name']); ?></span>
                                                    <small class="text-muted"><?= intval($pkg['activations_count']); ?> subscriptions</small>
                                                </div>
                                                <span class="badge bg-label-primary font-monospace fs-6">
                                                    <?= number_format(floatval($pkg['total_credits_spent']), 2); ?> pts
                                                </span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <p class="text-muted text-center py-3 mb-0">No package consumption data recorded.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Master User & Reseller Financial Ledger -->
    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center bg-transparent border-bottom gap-2">
            <div>
                <h5 class="card-title mb-1">
                    <i class="ti tabler-list-details me-2 text-primary"></i><?= htmlspecialchars($language::get('billing_finance_ledger_title')); ?>
                </h5>
                <small class="text-muted"><?= htmlspecialchars($language::get('billing_finance_ledger_desc')); ?></small>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <!-- Group Filter -->
                <select class="form-select form-select-sm" id="ledgerGroupFilter" style="width: auto;">
                    <option value=""><?= htmlspecialchars($language::get('billing_common_all_statuses')); ?> (All Accounts)</option>
                    <option value="resellers"><?= htmlspecialchars($language::get('billing_admin_nav_resellers')); ?></option>
                    <option value="admins">Admins</option>
                </select>

                <!-- Search Input -->
                <div class="input-group input-group-sm" style="width: 220px;">
                    <span class="input-group-text"><i class="ti tabler-search"></i></span>
                    <input type="text" class="form-control" id="ledgerSearchInput" placeholder="Search account...">
                </div>
            </div>
        </div>

        <div class="card-datatable table-responsive">
            <table class="table table-hover align-middle mb-0" id="financeLedgerTable" style="width: 100%;">
                <thead class="table-light">
                    <tr>
                        <th><?= htmlspecialchars($language::get('billing_finance_ledger_user')); ?></th>
                        <th><?= htmlspecialchars($language::get('billing_finance_ledger_group')); ?></th>
                        <th><?= htmlspecialchars($language::get('billing_finance_ledger_parent')); ?></th>
                        <th class="text-end"><?= htmlspecialchars($language::get('billing_finance_ledger_current_balance')); ?></th>
                        <th class="text-end"><?= htmlspecialchars($language::get('billing_finance_ledger_cash_paid')); ?></th>
                        <th class="text-end"><?= htmlspecialchars($language::get('billing_finance_ledger_points_bought')); ?></th>
                        <th class="text-end"><?= htmlspecialchars($language::get('billing_finance_ledger_points_spent')); ?></th>
                        <th class="text-center"><?= htmlspecialchars($language::get('billing_finance_ledger_sub_count')); ?></th>
                        <th class="text-end"><?= htmlspecialchars($language::get('billing_finance_ledger_profit')); ?></th>
                        <th class="text-center no-sort"><?= htmlspecialchars($language::get('billing_common_actions')); ?></th>
                    </tr>
                </thead>
                <tbody id="financeLedgerTbody">
                    <?php if (!empty($ledger)): ?>
                        <?php foreach ($ledger as $row): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-sm bg-label-primary rounded-circle me-2 d-flex align-items-center justify-content-center">
                                            <?= strtoupper(substr($row['username'] ?? 'U', 0, 1)); ?>
                                        </div>
                                        <div>
                                            <span class="fw-bold text-dark d-block">
                                                <?= htmlspecialchars($row['username']); ?>
                                                <small class="text-muted font-monospace">#<?= intval($row['id']); ?></small>
                                            </span>
                                            <small class="text-muted"><?= htmlspecialchars($row['email'] ?: 'No email'); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($row['is_admin'])): ?>
                                        <span class="badge bg-label-danger"><i class="ti tabler-shield me-1"></i>Admin</span>
                                    <?php elseif (!empty($row['is_reseller'])): ?>
                                        <span class="badge bg-label-primary"><i class="ti tabler-users me-1"></i>Reseller</span>
                                    <?php else: ?>
                                        <span class="badge bg-label-secondary">Sub-User</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($row['owner_username'])): ?>
                                        <span class="badge bg-label-info"><?= htmlspecialchars($row['owner_username']); ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-label-secondary">Direct (Admin)</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end font-monospace fw-bold <?= $row['credits'] > 0 ? 'text-success' : 'text-muted'; ?>">
                                    <?= number_format($row['credits'], 2); ?>
                                </td>
                                <td class="text-end font-monospace text-primary fw-semibold">
                                    <?= number_format($row['total_cash_paid'], 2); ?>
                                </td>
                                <td class="text-end font-monospace text-success">
                                    <?= number_format($row['total_points_bought'], 2); ?>
                                </td>
                                <td class="text-end font-monospace text-danger">
                                    <?= number_format($row['total_points_spent'], 2); ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($row['sub_resellers_count'] > 0): ?>
                                        <span class="badge bg-label-dark rounded-pill"><?= intval($row['sub_resellers_count']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end font-monospace fw-bold <?= $row['estimated_profit'] > 0 ? 'text-success' : 'text-muted'; ?>">
                                    <?= number_format($row['estimated_profit'], 2); ?>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-primary btn-statement" data-user-id="<?= intval($row['id']); ?>" data-username="<?= htmlspecialchars($row['username']); ?>">
                                        <i class="ti tabler-file-invoice me-1"></i><?= htmlspecialchars($language::get('billing_finance_ledger_statement_btn')); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">
                                <?= htmlspecialchars($language::get('billing_common_no_records')); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: User Detailed Itemized Statement of Account -->
<div class="modal fade" id="statementModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom py-3 bg-light">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-md bg-label-primary rounded-circle me-3 d-flex align-items-center justify-content-center">
                        <i class="ti tabler-file-invoice fs-2"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="statementModalTitle"><?= htmlspecialchars($language::get('billing_finance_statement_title')); ?></h5>
                        <small class="text-muted" id="statementModalSubtitle"></small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="statementPrintBtn">
                        <i class="ti tabler-printer me-1"></i><?= htmlspecialchars($language::get('billing_finance_statement_print')); ?>
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>

            <div class="modal-body p-4" id="statementPrintArea">
                <!-- Statement Client Header Info -->
                <div class="row g-3 p-3 bg-light rounded border mb-4">
                    <div class="col-md-3">
                        <small class="text-muted d-block"><?= htmlspecialchars($language::get('billing_finance_statement_client')); ?></small>
                        <strong class="fs-6 text-dark" id="stmtUsername">-</strong>
                        <div class="small text-muted" id="stmtEmail"></div>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block"><?= htmlspecialchars($language::get('billing_finance_statement_total_paid')); ?></small>
                        <strong class="fs-5 text-primary" id="stmtTotalCashPaid">0.00</strong>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block"><?= htmlspecialchars($language::get('billing_finance_statement_total_in')); ?> / Out</small>
                        <span class="fs-6 text-success fw-bold" id="stmtTotalIn">+0.00</span> /
                        <span class="fs-6 text-danger fw-bold" id="stmtTotalOut">-0.00</span>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block"><?= htmlspecialchars($language::get('billing_finance_ledger_current_balance')); ?></small>
                        <strong class="fs-4 text-warning" id="stmtCurrentBalance">0.00</strong> <small class="text-muted">pts</small>
                    </div>
                </div>

                <!-- Ledger Statement Items Table -->
                <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle" id="statementTable">
                        <thead class="table-light">
                            <tr>
                                <th><?= htmlspecialchars($language::get('billing_finance_statement_date')); ?></th>
                                <th><?= htmlspecialchars($language::get('billing_finance_statement_ref')); ?></th>
                                <th><?= htmlspecialchars($language::get('billing_finance_statement_desc')); ?></th>
                                <th class="text-end"><?= htmlspecialchars($language::get('billing_finance_statement_in')); ?></th>
                                <th class="text-end"><?= htmlspecialchars($language::get('billing_finance_statement_out')); ?></th>
                                <th class="text-end"><?= htmlspecialchars($language::get('billing_finance_statement_fiat')); ?></th>
                                <th class="text-center"><?= htmlspecialchars($language::get('billing_finance_statement_status')); ?></th>
                            </tr>
                        </thead>
                        <tbody id="statementTbody">
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <div class="spinner-border text-primary spinner-border-sm me-2" role="status"></div>
                                    Loading statement details...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer border-top py-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= htmlspecialchars($language::get('billing_common_close')); ?></button>
            </div>
        </div>
    </div>
</div>

<?php
LayoutRenderer::renderFooter('admin');
include __DIR__ . '/finance_scripts.php';
?>
