<?php
use XcVm\Core\Util\LayoutRenderer;
use XcVm\Core\Localization\Translator;

/**
 * @var array  $adminMethods
 * @var array  $resellerMethods
 * @var string $activeType
 * @var array  $methods
 */
$adminMethods = $adminMethods ?? array_filter($methods ?? [], fn($m) => (int) ($m['reseller_id'] ?? 0) === 0);
$resellerMethods = $resellerMethods ?? array_filter($methods ?? [], fn($m) => (int) ($m['reseller_id'] ?? 0) > 0);
$activeType = $activeType ?? (($_GET['type'] ?? 'admin') === 'reseller' ? 'reseller' : 'admin');
$language = Translator::class;
?>
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1"><i class="ti tabler-settings me-2"></i><?= htmlspecialchars($language::get('billing_admin_methods_title')); ?></h4>
            <p class="text-muted mb-0"><?= htmlspecialchars($language::get('billing_admin_methods_desc')); ?></p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary" id="btnOpenAddMethod">
                <i class="ti tabler-plus me-1"></i> <?= htmlspecialchars($language::get('billing_admin_add_method')); ?>
            </button>
            <a href="billing" class="btn btn-outline-secondary">
                <i class="ti tabler-arrow-left me-1"></i> <?= htmlspecialchars($language::get('billing_common_back_billing')); ?>
            </a>
        </div>
    </div>

    <!-- Quick Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-md bg-label-primary rounded-circle me-3 d-flex align-items-center justify-content-center">
                        <i class="ti tabler-shield-lock fs-3"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block fw-semibold">Admin Methods</small>
                        <h4 class="mb-0 fw-bold"><?= count($adminMethods); ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-md bg-label-info rounded-circle me-3 d-flex align-items-center justify-content-center">
                        <i class="ti tabler-users fs-3"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block fw-semibold">Reseller Methods</small>
                        <h4 class="mb-0 fw-bold"><?= count($resellerMethods); ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-md bg-label-success rounded-circle me-3 d-flex align-items-center justify-content-center">
                        <i class="ti tabler-circle-check fs-3"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block fw-semibold">Total Active</small>
                        <h4 class="mb-0 fw-bold">
                            <?php
                            $activeCount = 0;
                            foreach (array_merge($adminMethods, $resellerMethods) as $m) {
                                if ((int) $m['active'] === 1) $activeCount++;
                            }
                            echo $activeCount;
                            ?>
                        </h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-md bg-label-warning rounded-circle me-3 d-flex align-items-center justify-content-center">
                        <i class="ti tabler-credit-card fs-3"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block fw-semibold">All Platform Methods</small>
                        <h4 class="mb-0 fw-bold"><?= count($adminMethods) + count($resellerMethods); ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Nav Tabs Navigation -->
    <ul class="nav nav-pills mb-4" id="methodsNavTabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link <?= ($activeType === 'admin') ? 'active' : ''; ?> d-flex align-items-center" id="tab-admin-methods" data-bs-toggle="pill" data-bs-target="#pane-admin-methods" type="button" role="tab">
                <i class="ti tabler-shield-lock me-2"></i>
                <span class="fw-semibold"><?= htmlspecialchars($language::get('billing_admin_nav_methods_admin')); ?></span>
                <span class="badge bg-primary ms-2 rounded-pill"><?= count($adminMethods); ?></span>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link <?= ($activeType === 'reseller') ? 'active' : ''; ?> d-flex align-items-center" id="tab-reseller-methods" data-bs-toggle="pill" data-bs-target="#pane-reseller-methods" type="button" role="tab">
                <i class="ti tabler-users me-2"></i>
                <span class="fw-semibold"><?= htmlspecialchars($language::get('billing_admin_nav_methods_reseller')); ?></span>
                <span class="badge bg-label-info ms-2 rounded-pill"><?= count($resellerMethods); ?></span>
            </button>
        </li>
    </ul>

    <!-- Tab Content Panes -->
    <div class="tab-content p-0 border-0" id="methodsTabPanes">
        <!-- ─── PANE 1: ADMIN METHODS ─── -->
        <div class="tab-pane fade <?= ($activeType === 'admin') ? 'show active' : ''; ?>" id="pane-admin-methods" role="tabpanel">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title mb-0"><i class="ti tabler-shield-lock me-2 text-primary"></i><?= htmlspecialchars($language::get('billing_admin_methods_system_title')); ?></h5>
                        <small class="text-muted"><?= htmlspecialchars($language::get('billing_admin_methods_system_desc')); ?></small>
                    </div>
                    <button type="button" class="btn btn-sm btn-primary" onclick="document.getElementById('btnOpenAddMethod').click()">
                        <i class="ti tabler-plus me-1"></i> <?= htmlspecialchars($language::get('billing_admin_add_method')); ?>
                    </button>
                </div>
                <div class="card-datatable table-responsive">
                    <table class="table table-hover align-middle mb-0" id="adminMethodsTable" style="width:100%">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th><?= htmlspecialchars($language::get('billing_common_payment_method')); ?></th>
                                <th>Slug</th>
                                <th><?= htmlspecialchars($language::get('billing_common_rate')); ?></th>
                                <th><?= htmlspecialchars($language::get('billing_reseller_limits')); ?></th>
                                <th><?= htmlspecialchars($language::get('billing_common_status')); ?></th>
                                <th><?= htmlspecialchars($language::get('billing_common_date')); ?></th>
                                <th class="text-end no-sort"><?= htmlspecialchars($language::get('billing_common_actions')); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($adminMethods)): ?>
                                <?php foreach ($adminMethods as $m): ?>
                                    <tr>
                                        <td class="font-monospace text-muted">#<?= (int) $m['id']; ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-sm bg-label-primary rounded-circle me-2 d-flex align-items-center justify-content-center">
                                                    <i class="<?= htmlspecialchars((string) ($m['icon'] ?: 'ti tabler-credit-card')); ?>"></i>
                                                </div>
                                                <span class="fw-bold text-dark"><?= htmlspecialchars((string) $m['name']); ?></span>
                                            </div>
                                        </td>
                                        <td><code><?= htmlspecialchars((string) $m['slug']); ?></code></td>
                                        <td>
                                            <span class="fw-semibold"><?= number_format(floatval($m['rate_per_point']), 2); ?></span>
                                            <small class="text-muted"><?= htmlspecialchars((string) $m['currency']); ?> / pt</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-label-secondary"><?= intval($m['min_points']); ?></span> to 
                                            <span class="badge bg-label-secondary"><?= (intval($m['max_points']) > 0) ? intval($m['max_points']) : '∞'; ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-label-<?= ($m['active'] == 1) ? 'success' : 'secondary'; ?>">
                                                <?= ($m['active'] == 1) ? 'Active' : 'Disabled'; ?>
                                            </span>
                                        </td>
                                        <td class="small text-muted">
                                            <?= date('Y-m-d', intval($m['updated_at'])); ?>
                                        </td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-label-info view-admin-inst-btn me-1"
                                                    data-id="<?= (int) $m['id']; ?>"
                                                    data-name="<?= htmlspecialchars((string) $m['name'], ENT_QUOTES); ?>"
                                                    data-inst="<?= htmlspecialchars((string) $m['instructions'], ENT_QUOTES); ?>"
                                                    title="Preview Instructions">
                                                <i class="ti tabler-eye"></i>
                                            </button>
                                            <textarea id="admin-inst-raw-<?= (int) $m['id']; ?>" class="d-none"><?= htmlspecialchars((string) $m['instructions']); ?></textarea>
                                            <button type="button" class="btn btn-sm btn-label-secondary edit-method-btn me-1"
                                                    data-id="<?= (int) $m['id']; ?>"
                                                    data-name="<?= htmlspecialchars((string) $m['name'], ENT_QUOTES); ?>"
                                                    data-slug="<?= htmlspecialchars((string) $m['slug'], ENT_QUOTES); ?>"
                                                    data-icon="<?= htmlspecialchars((string) $m['icon'], ENT_QUOTES); ?>"
                                                    data-rate="<?= floatval($m['rate_per_point']); ?>"
                                                    data-currency="<?= htmlspecialchars((string) $m['currency'], ENT_QUOTES); ?>"
                                                    data-min="<?= intval($m['min_points']); ?>"
                                                    data-max="<?= intval($m['max_points']); ?>"
                                                    data-active="<?= (int) $m['active']; ?>"
                                                    data-instructions="<?= htmlspecialchars((string) $m['instructions'], ENT_QUOTES); ?>"
                                                    data-reseller="0"
                                                    data-reseller-name="Admin">
                                                <i class="ti tabler-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-label-danger delete-method-btn" data-id="<?= (int) $m['id']; ?>">
                                                <i class="ti tabler-trash"></i>
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

        <!-- ─── PANE 2: RESELLER METHODS ─── -->
        <div class="tab-pane fade <?= ($activeType === 'reseller') ? 'show active' : ''; ?>" id="pane-reseller-methods" role="tabpanel">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h5 class="card-title mb-0"><i class="ti tabler-users me-2 text-info"></i>Reseller Custom Payment Methods</h5>
                        <small class="text-muted">Payment methods created and managed by individual resellers for their sub-resellers.</small>
                    </div>
                    <div class="d-flex align-items-center" style="max-width: 280px;">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="ti tabler-search"></i></span>
                            <input type="text" id="resellerMethodsFilterInput" class="form-control" placeholder="Search reseller or method...">
                        </div>
                    </div>
                </div>
                <div class="card-datatable table-responsive">
                    <table class="table table-hover align-middle mb-0" id="resellerMethodsTable" style="width:100%">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Reseller Owner</th>
                                <th>Method</th>
                                <th>Slug</th>
                                <th>Exchange Rate</th>
                                <th>Min / Max Points</th>
                                <th>Status</th>
                                <th>Updated</th>
                                <th class="text-end no-sort">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($resellerMethods)): ?>
                                <?php foreach ($resellerMethods as $m): ?>
                                    <tr class="reseller-method-row" data-search="<?= strtolower(htmlspecialchars((string) (($m['reseller_username'] ?? '') . ' ' . $m['name'] . ' ' . $m['slug']))); ?>">
                                        <td class="font-monospace text-muted">#<?= (int) $m['id']; ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-sm bg-label-info rounded-circle me-2 d-flex align-items-center justify-content-center">
                                                    <i class="ti tabler-user"></i>
                                                </div>
                                                <div>
                                                    <span class="fw-bold text-dark d-block"><?= htmlspecialchars((string) ($m['reseller_username'] ?: '#' . $m['reseller_id'])); ?></span>
                                                    <small class="text-muted font-monospace">Reseller #<?= (int) $m['reseller_id']; ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-sm bg-label-primary rounded-circle me-2 d-flex align-items-center justify-content-center">
                                                    <i class="<?= htmlspecialchars((string) ($m['icon'] ?: 'ti tabler-credit-card')); ?>"></i>
                                                </div>
                                                <span class="fw-bold text-dark"><?= htmlspecialchars((string) $m['name']); ?></span>
                                            </div>
                                        </td>
                                        <td><code><?= htmlspecialchars((string) $m['slug']); ?></code></td>
                                        <td>
                                            <span class="fw-semibold"><?= number_format(floatval($m['rate_per_point']), 2); ?></span>
                                            <small class="text-muted"><?= htmlspecialchars((string) $m['currency']); ?> / pt</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-label-secondary"><?= intval($m['min_points']); ?></span> to 
                                            <span class="badge bg-label-secondary"><?= (intval($m['max_points']) > 0) ? intval($m['max_points']) : '∞'; ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-label-<?= ($m['active'] == 1) ? 'success' : 'secondary'; ?>">
                                                <?= ($m['active'] == 1) ? 'Active' : 'Disabled'; ?>
                                            </span>
                                        </td>
                                        <td class="small text-muted">
                                            <?= date('Y-m-d', intval($m['updated_at'])); ?>
                                        </td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-label-info view-admin-inst-btn me-1"
                                                    data-id="<?= (int) $m['id']; ?>"
                                                    data-name="<?= htmlspecialchars((string) $m['name'], ENT_QUOTES); ?>"
                                                    data-inst="<?= htmlspecialchars((string) $m['instructions'], ENT_QUOTES); ?>"
                                                    title="Preview Instructions">
                                                <i class="ti tabler-eye"></i>
                                            </button>
                                            <textarea id="admin-inst-raw-<?= (int) $m['id']; ?>" class="d-none"><?= htmlspecialchars((string) $m['instructions']); ?></textarea>
                                            <button type="button" class="btn btn-sm btn-label-secondary edit-method-btn me-1"
                                                    data-id="<?= (int) $m['id']; ?>"
                                                    data-name="<?= htmlspecialchars((string) $m['name'], ENT_QUOTES); ?>"
                                                    data-slug="<?= htmlspecialchars((string) $m['slug'], ENT_QUOTES); ?>"
                                                    data-icon="<?= htmlspecialchars((string) $m['icon'], ENT_QUOTES); ?>"
                                                    data-rate="<?= floatval($m['rate_per_point']); ?>"
                                                    data-currency="<?= htmlspecialchars((string) $m['currency'], ENT_QUOTES); ?>"
                                                    data-min="<?= intval($m['min_points']); ?>"
                                                    data-max="<?= intval($m['max_points']); ?>"
                                                    data-active="<?= (int) $m['active']; ?>"
                                                    data-instructions="<?= htmlspecialchars((string) $m['instructions'], ENT_QUOTES); ?>"
                                                    data-reseller="<?= (int) $m['reseller_id']; ?>"
                                                    data-reseller-name="<?= htmlspecialchars((string) ($m['reseller_username'] ?: '#' . $m['reseller_id']), ENT_QUOTES); ?>">
                                                <i class="ti tabler-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-label-danger delete-method-btn" data-id="<?= (int) $m['id']; ?>">
                                                <i class="ti tabler-trash"></i>
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
    </div>
</div>

<!-- Modal: Add / Edit Payment Method -->
<div class="modal fade" id="methodFormModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="methodForm">
                <input type="hidden" name="id" id="methodId" value="">
                <input type="hidden" name="reseller_id" id="methodResellerId" value="0">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title" id="methodModalTitle">
                        <i class="ti tabler-plus me-2"></i>Add Payment Method
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Method Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="methodName" class="form-control" placeholder="e.g. Vodafone Cash, USDT TRC20, Bank Wire" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Slug / Identifier</label>
                            <input type="text" name="slug" id="methodSlug" class="form-control" placeholder="e.g. vodafone_cash, usdt_trc20">
                            <small class="text-muted">Unique code for tracking and tags.</small>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Icon Class</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light cursor-pointer" id="btnAdminIconPreviewTrigger" title="Click to browse icons"><i id="iconPreview" class="ti tabler-credit-card fs-4 text-primary"></i></span>
                                <input type="text" name="icon" id="methodIcon" class="form-control" value="ti tabler-credit-card">
                                <button type="button" class="btn btn-outline-primary" id="btnAdminOpenIconPicker" title="Choose from Library">
                                    <i class="ti tabler-icons"></i> Browse
                                </button>
                            </div>
                            <small class="text-muted">Click Browse to pick from icon library or type class.</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Rate Per Point</label>
                            <input type="number" step="0.0001" min="0.0001" name="rate_per_point" id="methodRate" class="form-control" value="1.0000" required>
                            <small class="text-muted">Amount of currency for 1 credit.</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Currency Code</label>
                            <input type="text" name="currency" id="methodCurrency" class="form-control" value="USD" placeholder="USD, EGP, SAR, EUR" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Min Points</label>
                            <input type="number" min="1" name="min_points" id="methodMin" class="form-control" value="1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Max Points (0 = unlimited)</label>
                            <input type="number" min="0" name="max_points" id="methodMax" class="form-control" value="0">
                        </div>
                    </div>

                    <!-- Rich HTML & Text Editor Section -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-2">
                            <label class="form-label fw-semibold mb-0">HTML Instructions & Account Details <span class="text-danger">*</span></label>
                            
                            <!-- Mode Switcher Tabs -->
                            <div class="btn-group btn-group-sm" role="group" id="adminEditorModeGroup">
                                <button type="button" class="btn btn-primary active admin-editor-mode-btn" data-mode="split" title="Side by side: Visual + HTML Source">
                                    <i class="ti tabler-columns me-1"></i> Split (Text + HTML)
                                </button>
                                <button type="button" class="btn btn-outline-primary admin-editor-mode-btn" data-mode="visual" title="Visual Rich Text Only">
                                    <i class="ti tabler-edit me-1"></i> Visual Only
                                </button>
                                <button type="button" class="btn btn-outline-primary admin-editor-mode-btn" data-mode="html" title="HTML Source Code Only">
                                    <i class="ti tabler-code me-1"></i> HTML Source
                                </button>
                                <button type="button" class="btn btn-outline-primary admin-editor-mode-btn" data-mode="preview" title="Live Buyer Checkout Preview">
                                    <i class="ti tabler-eye me-1"></i> Live Preview
                                </button>
                            </div>
                        </div>

                        <!-- Quick Templates & Snippets Bar -->
                        <div class="d-flex align-items-center gap-1 mb-2 flex-wrap bg-light p-2 rounded border">
                            <span class="small fw-semibold text-muted me-1"><i class="ti tabler-template me-1"></i>Presets:</span>
                            <button type="button" class="btn btn-xs btn-outline-secondary admin-template-btn" data-template="vodafone">
                                <i class="ti tabler-device-mobile text-danger me-1"></i>Vodafone Cash
                            </button>
                            <button type="button" class="btn btn-xs btn-outline-secondary admin-template-btn" data-template="instapay">
                                <i class="ti tabler-send text-info me-1"></i>InstaPay
                            </button>
                            <button type="button" class="btn btn-xs btn-outline-secondary admin-template-btn" data-template="bank">
                                <i class="ti tabler-building-bank text-primary me-1"></i>Bank IBAN
                            </button>
                            <button type="button" class="btn btn-xs btn-outline-secondary admin-template-btn" data-template="crypto">
                                <i class="ti tabler-diamond text-success me-1"></i>USDT TRC20
                            </button>
                            
                            <div class="vr mx-1"></div>
                            <span class="small fw-semibold text-muted me-1">Snippets:</span>
                            <button type="button" class="btn btn-xs btn-outline-dark admin-snippet-btn" data-snippet="copybox" title="Insert copyable container">
                                <i class="ti tabler-copy me-1"></i>Copy Box
                            </button>
                            <button type="button" class="btn btn-xs btn-outline-warning admin-snippet-btn" data-snippet="alert" title="Insert warning note">
                                <i class="ti tabler-alert-triangle me-1"></i>Warning Alert
                            </button>
                        </div>

                        <!-- Editor Wrapper -->
                        <div class="border rounded bg-white shadow-sm overflow-hidden" id="adminEditorWrapper">
                            <!-- Visual Toolbar (Shown in Visual or Split Mode) -->
                            <div class="p-2 border-bottom bg-light d-flex flex-wrap align-items-center gap-1" id="adminVisualToolbar">
                                <!-- Text Formatting -->
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-secondary admin-cmd-btn" data-cmd="bold" title="Bold (Ctrl+B)">
                                        <i class="ti tabler-bold"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary admin-cmd-btn" data-cmd="italic" title="Italic (Ctrl+I)">
                                        <i class="ti tabler-italic"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary admin-cmd-btn" data-cmd="underline" title="Underline (Ctrl+U)">
                                        <i class="ti tabler-underline"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary admin-cmd-btn" data-cmd="strikeThrough" title="Strikethrough">
                                        <i class="ti tabler-strikethrough"></i>
                                    </button>
                                </div>

                                <!-- Headings -->
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-secondary admin-format-btn" data-tag="h4" title="Heading 4">
                                        <span class="fw-bold" style="font-size: 0.78rem;">H4</span>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary admin-format-btn" data-tag="h5" title="Heading 5">
                                        <span class="fw-bold" style="font-size: 0.78rem;">H5</span>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary admin-format-btn" data-tag="p" title="Paragraph">
                                        <span style="font-size: 0.78rem;">¶</span>
                                    </button>
                                </div>

                                <!-- Colors -->
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-secondary admin-color-btn" data-color="#0052cc" title="Blue">
                                        <span class="d-inline-block rounded-circle" style="width: 11px; height: 11px; background: #0052cc;"></span>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary admin-color-btn" data-color="#28c76f" title="Green">
                                        <span class="d-inline-block rounded-circle" style="width: 11px; height: 11px; background: #28c76f;"></span>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary admin-color-btn" data-color="#ea5455" title="Red">
                                        <span class="d-inline-block rounded-circle" style="width: 11px; height: 11px; background: #ea5455;"></span>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary admin-color-btn" data-color="#ff9f43" title="Amber">
                                        <span class="d-inline-block rounded-circle" style="width: 11px; height: 11px; background: #ff9f43;"></span>
                                    </button>
                                </div>

                                <!-- Lists & Alignment -->
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-secondary admin-cmd-btn" data-cmd="insertUnorderedList" title="Bullet List">
                                        <i class="ti tabler-list"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary admin-cmd-btn" data-cmd="insertOrderedList" title="Numbered List">
                                        <i class="ti tabler-list-numbers"></i>
                                    </button>
                                </div>

                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-secondary admin-cmd-btn" data-cmd="justifyLeft" title="Align Left">
                                        <i class="ti tabler-align-left"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary admin-cmd-btn" data-cmd="justifyCenter" title="Align Center">
                                        <i class="ti tabler-align-center"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary admin-cmd-btn" data-cmd="justifyRight" title="Align Right">
                                        <i class="ti tabler-align-right"></i>
                                    </button>
                                </div>

                                <!-- Insert Links & Horizontal Rule -->
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-secondary" id="btnAdminInsertLink" title="Insert Link">
                                        <i class="ti tabler-link"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary admin-cmd-btn" data-cmd="insertHorizontalRule" title="Divider">
                                        <i class="ti tabler-separator"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary admin-cmd-btn" data-cmd="removeFormat" title="Clear Formatting">
                                        <i class="ti tabler-clear-formatting"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Editor Body: Split / Visual / HTML / Preview -->
                            <div class="row g-0" id="adminEditorPanesRow">
                                <!-- Left Column: Visual Canvas -->
                                <div class="col-md-6 border-end" id="adminVisualCol">
                                    <div class="bg-light px-3 py-1 border-bottom small text-muted d-flex justify-content-between align-items-center">
                                        <span><i class="ti tabler-edit me-1 text-primary"></i>Visual Editor (Formatted Text)</span>
                                        <span class="badge bg-label-primary font-monospace" style="font-size: 0.65rem;">WYSIWYG</span>
                                    </div>
                                    <div id="adminVisualCanvas" contenteditable="true" class="p-3 bg-white" style="min-height: 220px; max-height: 380px; overflow-y: auto; outline: none; line-height: 1.6;" placeholder="Type payment instructions here..."></div>
                                </div>

                                <!-- Right Column: HTML Source Code -->
                                <div class="col-md-6" id="adminHtmlCol">
                                    <div class="bg-dark px-3 py-1 border-bottom small text-light d-flex justify-content-between align-items-center">
                                        <span><i class="ti tabler-code me-1 text-warning"></i>HTML Source Code</span>
                                        <span class="badge bg-secondary font-monospace" style="font-size: 0.65rem;">RAW HTML</span>
                                    </div>
                                    <textarea id="adminHtmlSource" class="form-control font-monospace border-0 rounded-0 p-3 bg-dark text-light" rows="9" style="min-height: 220px; max-height: 380px; font-size: 0.82rem; line-height: 1.5; resize: none;" placeholder="<p>HTML code appears here...</p>"></textarea>
                                </div>
                            </div>

                            <!-- Live Buyer Preview Container (Shown in Preview Mode) -->
                            <div id="adminLivePreviewContainer" class="p-3 bg-light border-top d-none" style="min-height: 220px; max-height: 380px; overflow-y: auto;">
                                <div class="alert alert-info py-2 px-3 mb-2 small"><i class="ti tabler-eye me-1"></i> <strong>Live Buyer Preview:</strong> This is how the instructions will appear to the buyer during Step 3 of checkout.</div>
                                <div id="adminLivePreviewBody" class="p-3 bg-white border rounded shadow-xs"></div>
                            </div>
                        </div>

                        <!-- Underlying actual form input (synchronized) -->
                        <textarea name="instructions" id="methodInstructions" class="d-none" required></textarea>
                        <small class="text-muted d-block mt-1">Visual text and HTML code synchronize in real time. Buyers see the formatted design during checkout.</small>
                    </div>

                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="active" id="methodActive" value="1" checked>
                        <label class="form-check-label fw-semibold" for="methodActive">Active and available for checkout</label>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="btnSaveMethod">
                        <i class="ti tabler-device-floppy me-1"></i> Save Method
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Icon Picker Library -->
<div class="modal fade" id="adminIconPickerModal" tabindex="-1" aria-hidden="true" style="z-index: 1105;">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <div>
                    <h5 class="modal-title mb-0"><i class="ti tabler-icons me-2 text-primary"></i>Payment & Transfer Icon Library</h5>
                    <small class="text-muted">Select an icon to visually represent your payment method.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Search & Filters -->
                <div class="row g-2 mb-3">
                    <div class="col-md-7">
                        <div class="input-group">
                            <span class="input-group-text"><i class="ti tabler-search"></i></span>
                            <input type="text" id="adminIconSearchInput" class="form-control" placeholder="Search icons (e.g. card, bank, wallet, cash, btc, mobile, qr)...">
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="d-flex gap-1 flex-wrap" id="adminIconCategoryFilters">
                            <button type="button" class="btn btn-xs btn-primary admin-icon-cat-filter active" data-cat="all">All</button>
                            <button type="button" class="btn btn-xs btn-outline-secondary admin-icon-cat-filter" data-cat="payment">Cash & Pay</button>
                            <button type="button" class="btn btn-xs btn-outline-secondary admin-icon-cat-filter" data-cat="banking">Bank & Wallet</button>
                            <button type="button" class="btn btn-xs btn-outline-secondary admin-icon-cat-filter" data-cat="crypto">Crypto & Fiat</button>
                            <button type="button" class="btn btn-xs btn-outline-secondary admin-icon-cat-filter" data-cat="brands">Brands</button>
                        </div>
                    </div>
                </div>

                <!-- Icons Grid -->
                <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-6 g-2" id="adminIconLibraryGrid" style="max-height: 420px; overflow-y: auto;">
                    <!-- Rendered by JS -->
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: View Instructions Preview -->
<div class="modal fade" id="adminPreviewInstructionsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title"><i class="ti tabler-info-circle me-2"></i>Instructions: <span id="adminPrevModalMethodName" class="text-primary"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div id="adminPrevModalContent" class="p-3 border rounded bg-light"></div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php
LayoutRenderer::renderFooter('admin');
include __DIR__ . '/methods_scripts.php';
?>
