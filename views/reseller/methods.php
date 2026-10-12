<?php
use XcVm\Core\Util\LayoutRenderer;
use XcVm\Core\Localization\Translator;

/** @var array $methods */
$language = Translator::class;
?>
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="ti tabler-wallet me-2"></i><?= htmlspecialchars($language::get('billing_reseller_nav_methods')); ?></h4>
            <p class="text-muted mb-0"><?= htmlspecialchars($language::get('billing_reseller_hub_desc')); ?></p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary" id="btnAddNewMethod">
                <i class="ti tabler-plus me-1"></i> <?= htmlspecialchars($language::get('billing_admin_add_method')); ?>
            </button>
            <a href="billing" class="btn btn-outline-secondary">
                <i class="ti tabler-arrow-left me-1"></i> <?= htmlspecialchars($language::get('billing_subreseller_back_wallet')); ?>
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-bottom">
            <h5 class="card-title mb-0"><i class="ti tabler-list me-2"></i><?= htmlspecialchars($language::get('billing_reseller_configured_methods')); ?></h5>
        </div>
        <div class="card-datatable table-responsive">
            <table class="table table-hover align-middle mb-0" id="resellerMethodsTable" style="width:100%">
                <thead class="table-light">
                    <tr>
                        <th><?= htmlspecialchars($language::get('billing_common_payment_method')); ?></th>
                        <th>Tag / Slug</th>
                        <th><?= htmlspecialchars($language::get('billing_common_rate')); ?></th>
                        <th><?= htmlspecialchars($language::get('billing_reseller_limits')); ?></th>
                        <th><?= htmlspecialchars($language::get('billing_subreseller_transfer_instructions')); ?></th>
                        <th><?= htmlspecialchars($language::get('billing_common_status')); ?></th>
                        <th class="text-end no-sort"><?= htmlspecialchars($language::get('billing_common_actions')); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($methods)): ?>
                        <?php foreach ($methods as $m): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-sm bg-label-primary rounded-circle me-2 d-flex align-items-center justify-content-center">
                                            <i class="<?= htmlspecialchars((string) ($m['icon'] ?: 'ti tabler-credit-card')); ?>"></i>
                                        </div>
                                        <span class="fw-bold text-dark fs-6"><?= htmlspecialchars((string) $m['name']); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-label-secondary font-monospace"><?= htmlspecialchars((string) $m['slug']); ?></span>
                                </td>
                                <td>
                                    <span class="fw-bold text-success"><?= number_format(floatval($m['rate_per_point']), 2); ?></span>
                                    <small class="text-muted"><?= htmlspecialchars((string) $m['currency']); ?> / point</small>
                                </td>
                                <td>
                                    <span class="badge bg-label-dark"><?= intval($m['min_points']); ?></span> to 
                                    <span class="badge bg-label-dark"><?= (intval($m['max_points']) > 0) ? intval($m['max_points']) : '∞'; ?></span> pts
                                </td>
                                <td>
                                    <button type="button" class="btn btn-xs btn-label-info view-inst-btn"
                                            data-id="<?= (int) $m['id']; ?>"
                                            data-name="<?= htmlspecialchars((string) $m['name'], ENT_QUOTES); ?>"
                                            data-inst="<?= htmlspecialchars((string) $m['instructions'], ENT_QUOTES); ?>">
                                        <i class="ti tabler-eye me-1"></i> View Instructions
                                    </button>
                                    <textarea id="res-inst-raw-<?= (int) $m['id']; ?>" class="d-none"><?= htmlspecialchars((string) $m['instructions']); ?></textarea>
                                </td>
                                <td>
                                    <span class="badge bg-label-<?= ($m['active'] == 1) ? 'success' : 'secondary'; ?>">
                                        <?= ($m['active'] == 1) ? 'Active' : 'Disabled'; ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-label-secondary edit-res-method"
                                            data-id="<?= (int) $m['id']; ?>"
                                            data-name="<?= htmlspecialchars((string) $m['name'], ENT_QUOTES); ?>"
                                            data-slug="<?= htmlspecialchars((string) $m['slug'], ENT_QUOTES); ?>"
                                            data-icon="<?= htmlspecialchars((string) $m['icon'], ENT_QUOTES); ?>"
                                            data-rate="<?= floatval($m['rate_per_point']); ?>"
                                            data-currency="<?= htmlspecialchars((string) $m['currency'], ENT_QUOTES); ?>"
                                            data-min="<?= intval($m['min_points']); ?>"
                                            data-max="<?= intval($m['max_points']); ?>"
                                            data-active="<?= (int) $m['active']; ?>"
                                            data-instructions="<?= htmlspecialchars((string) $m['instructions'], ENT_QUOTES); ?>">
                                        <i class="ti tabler-edit"></i> Edit
                                    </button>
                                    <button type="button" class="btn btn-sm btn-label-danger delete-res-method" data-id="<?= (int) $m['id']; ?>">
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

<!-- Modal: Reseller Add / Edit Method -->
<div class="modal fade" id="resMethodModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="resMethodForm">
                <input type="hidden" name="id" id="resMethodId" value="">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title" id="resMethodModalTitle">
                        <i class="ti tabler-plus me-2"></i>Configure Payment Method
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Method Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="resMethodName" class="form-control" placeholder="e.g. Vodafone Cash, InstaPay, USDT TRC20, CIB Bank" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Slug / Identifier</label>
                            <input type="text" name="slug" id="resMethodSlug" class="form-control" placeholder="e.g. vf_cash, instapay, usdt">
                            <small class="text-muted">Unique tag code for this method.</small>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Method Icon</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light cursor-pointer" id="btnResIconPreviewTrigger" title="Choose from Library"><i id="resIconPreview" class="ti tabler-credit-card fs-4 text-primary"></i></span>
                                <input type="text" name="icon" id="resMethodIcon" class="form-control" value="ti tabler-credit-card">
                                <button type="button" class="btn btn-outline-primary" id="btnOpenIconPicker" title="Choose from Library">
                                    <i class="ti tabler-icons"></i> Browse
                                </button>
                            </div>
                            <small class="text-muted">Click Browse to pick from icon library or type class.</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Price Per Point <span class="text-danger">*</span></label>
                            <input type="number" step="0.0001" min="0.0001" name="rate_per_point" id="resMethodRate" class="form-control" value="1.0000" required>
                            <small class="text-muted">Currency cost for 1 point.</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Currency Code <span class="text-danger">*</span></label>
                            <input type="text" name="currency" id="resMethodCurrency" class="form-control" value="USD" placeholder="USD, EGP, SAR, EUR" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Minimum Points per Order</label>
                            <input type="number" min="1" name="min_points" id="resMethodMin" class="form-control" value="1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Maximum Points per Order (0 = unlimited)</label>
                            <input type="number" min="0" name="max_points" id="resMethodMax" class="form-control" value="0">
                        </div>
                    </div>

                    <!-- Rich HTML & Text Editor Section -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-2">
                            <label class="form-label fw-semibold mb-0">Account Details & HTML Payment Instructions <span class="text-danger">*</span></label>
                            
                            <!-- Mode Switcher Tabs -->
                            <div class="btn-group btn-group-sm" role="group" id="resEditorModeGroup">
                                <button type="button" class="btn btn-primary active res-editor-mode-btn" data-mode="split" title="Side by side: Visual + HTML Source">
                                    <i class="ti tabler-columns me-1"></i> Split (Text + HTML)
                                </button>
                                <button type="button" class="btn btn-outline-primary res-editor-mode-btn" data-mode="visual" title="Visual Rich Text Only">
                                    <i class="ti tabler-edit me-1"></i> Visual Only
                                </button>
                                <button type="button" class="btn btn-outline-primary res-editor-mode-btn" data-mode="html" title="HTML Source Code Only">
                                    <i class="ti tabler-code me-1"></i> HTML Source
                                </button>
                                <button type="button" class="btn btn-outline-primary res-editor-mode-btn" data-mode="preview" title="Live Buyer Checkout Preview">
                                    <i class="ti tabler-eye me-1"></i> Live Preview
                                </button>
                            </div>
                        </div>

                        <!-- Quick Templates & Snippets Bar -->
                        <div class="d-flex align-items-center gap-1 mb-2 flex-wrap bg-light p-2 rounded border">
                            <span class="small fw-semibold text-muted me-1"><i class="ti tabler-template me-1"></i>Presets:</span>
                            <button type="button" class="btn btn-xs btn-outline-secondary res-template-btn" data-template="vodafone">
                                <i class="ti tabler-device-mobile text-danger me-1"></i>Vodafone Cash
                            </button>
                            <button type="button" class="btn btn-xs btn-outline-secondary res-template-btn" data-template="instapay">
                                <i class="ti tabler-send text-info me-1"></i>InstaPay
                            </button>
                            <button type="button" class="btn btn-xs btn-outline-secondary res-template-btn" data-template="bank">
                                <i class="ti tabler-building-bank text-primary me-1"></i>Bank IBAN
                            </button>
                            <button type="button" class="btn btn-xs btn-outline-secondary res-template-btn" data-template="crypto">
                                <i class="ti tabler-diamond text-success me-1"></i>USDT TRC20
                            </button>
                            
                            <div class="vr mx-1"></div>
                            <span class="small fw-semibold text-muted me-1">Snippets:</span>
                            <button type="button" class="btn btn-xs btn-outline-dark res-snippet-btn" data-snippet="copybox" title="Insert copyable container">
                                <i class="ti tabler-copy me-1"></i>Copy Box
                            </button>
                            <button type="button" class="btn btn-xs btn-outline-warning res-snippet-btn" data-snippet="alert" title="Insert warning note">
                                <i class="ti tabler-alert-triangle me-1"></i>Warning Alert
                            </button>
                        </div>

                        <!-- Editor Wrapper -->
                        <div class="border rounded bg-white shadow-sm overflow-hidden" id="resEditorWrapper">
                            <!-- Visual Toolbar (Shown in Visual or Split Mode) -->
                            <div class="p-2 border-bottom bg-light d-flex flex-wrap align-items-center gap-1" id="resVisualToolbar">
                                <!-- Text Formatting -->
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-secondary res-cmd-btn" data-cmd="bold" title="Bold (Ctrl+B)">
                                        <i class="ti tabler-bold"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary res-cmd-btn" data-cmd="italic" title="Italic (Ctrl+I)">
                                        <i class="ti tabler-italic"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary res-cmd-btn" data-cmd="underline" title="Underline (Ctrl+U)">
                                        <i class="ti tabler-underline"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary res-cmd-btn" data-cmd="strikeThrough" title="Strikethrough">
                                        <i class="ti tabler-strikethrough"></i>
                                    </button>
                                </div>

                                <!-- Headings -->
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-secondary res-format-btn" data-tag="h4" title="Heading 4">
                                        <span class="fw-bold" style="font-size: 0.78rem;">H4</span>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary res-format-btn" data-tag="h5" title="Heading 5">
                                        <span class="fw-bold" style="font-size: 0.78rem;">H5</span>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary res-format-btn" data-tag="p" title="Paragraph">
                                        <span style="font-size: 0.78rem;">¶</span>
                                    </button>
                                </div>

                                <!-- Colors -->
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-secondary res-color-btn" data-color="#0052cc" title="Blue">
                                        <span class="d-inline-block rounded-circle" style="width: 11px; height: 11px; background: #0052cc;"></span>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary res-color-btn" data-color="#28c76f" title="Green">
                                        <span class="d-inline-block rounded-circle" style="width: 11px; height: 11px; background: #28c76f;"></span>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary res-color-btn" data-color="#ea5455" title="Red">
                                        <span class="d-inline-block rounded-circle" style="width: 11px; height: 11px; background: #ea5455;"></span>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary res-color-btn" data-color="#ff9f43" title="Amber">
                                        <span class="d-inline-block rounded-circle" style="width: 11px; height: 11px; background: #ff9f43;"></span>
                                    </button>
                                </div>

                                <!-- Lists & Alignment -->
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-secondary res-cmd-btn" data-cmd="insertUnorderedList" title="Bullet List">
                                        <i class="ti tabler-list"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary res-cmd-btn" data-cmd="insertOrderedList" title="Numbered List">
                                        <i class="ti tabler-list-numbers"></i>
                                    </button>
                                </div>

                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-secondary res-cmd-btn" data-cmd="justifyLeft" title="Align Left">
                                        <i class="ti tabler-align-left"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary res-cmd-btn" data-cmd="justifyCenter" title="Align Center">
                                        <i class="ti tabler-align-center"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary res-cmd-btn" data-cmd="justifyRight" title="Align Right">
                                        <i class="ti tabler-align-right"></i>
                                    </button>
                                </div>

                                <!-- Insert Links & Horizontal Rule -->
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-secondary" id="btnResInsertLink" title="Insert Link">
                                        <i class="ti tabler-link"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary res-cmd-btn" data-cmd="insertHorizontalRule" title="Divider">
                                        <i class="ti tabler-separator"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary res-cmd-btn" data-cmd="removeFormat" title="Clear Formatting">
                                        <i class="ti tabler-clear-formatting"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Editor Body: Split / Visual / HTML / Preview -->
                            <div class="row g-0" id="resEditorPanesRow">
                                <!-- Left Column: Visual Canvas -->
                                <div class="col-md-6 border-end" id="resVisualCol">
                                    <div class="bg-light px-3 py-1 border-bottom small text-muted d-flex justify-content-between align-items-center">
                                        <span><i class="ti tabler-edit me-1 text-primary"></i>Visual Editor (Formatted Text)</span>
                                        <span class="badge bg-label-primary font-monospace" style="font-size: 0.65rem;">WYSIWYG</span>
                                    </div>
                                    <div id="resVisualCanvas" contenteditable="true" class="p-3 bg-white" style="min-height: 220px; max-height: 380px; overflow-y: auto; outline: none; line-height: 1.6;" placeholder="Type payment instructions here..."></div>
                                </div>

                                <!-- Right Column: HTML Source Code -->
                                <div class="col-md-6" id="resHtmlCol">
                                    <div class="bg-dark px-3 py-1 border-bottom small text-light d-flex justify-content-between align-items-center">
                                        <span><i class="ti tabler-code me-1 text-warning"></i>HTML Source Code</span>
                                        <span class="badge bg-secondary font-monospace" style="font-size: 0.65rem;">RAW HTML</span>
                                    </div>
                                    <textarea id="resHtmlSource" class="form-control font-monospace border-0 rounded-0 p-3 bg-dark text-light" rows="9" style="min-height: 220px; max-height: 380px; font-size: 0.82rem; line-height: 1.5; resize: none;" placeholder="<p>HTML code appears here...</p>"></textarea>
                                </div>
                            </div>

                            <!-- Live Buyer Preview Container (Shown in Preview Mode) -->
                            <div id="resLivePreviewContainer" class="p-3 bg-light border-top d-none" style="min-height: 220px; max-height: 380px; overflow-y: auto;">
                                <div class="alert alert-info py-2 px-3 mb-2 small"><i class="ti tabler-eye me-1"></i> <strong>Live Buyer Preview:</strong> This is how the instructions will appear to the buyer during Step 3 of checkout.</div>
                                <div id="resLivePreviewBody" class="p-3 bg-white border rounded shadow-xs"></div>
                            </div>
                        </div>

                        <!-- Underlying actual form input (synchronized) -->
                        <textarea name="instructions" id="resMethodInstructions" class="d-none" required></textarea>
                        <small class="text-muted d-block mt-1">Visual text and HTML code synchronize in real time. Buyers see the formatted design during checkout.</small>
                    </div>

                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="active" id="resMethodActive" value="1" checked>
                        <label class="form-check-label fw-semibold" for="resMethodActive">Enable this payment method for sub-resellers</label>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="btnResSaveMethod">
                        <i class="ti tabler-device-floppy me-1"></i> Save Payment Method
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Icon Picker Library -->
<div class="modal fade" id="iconPickerModal" tabindex="-1" aria-hidden="true" style="z-index: 1105;">
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
                            <input type="text" id="iconSearchInput" class="form-control" placeholder="Search icons (e.g. card, bank, wallet, cash, btc, mobile, qr)...">
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="d-flex gap-1 flex-wrap" id="iconCategoryFilters">
                            <button type="button" class="btn btn-xs btn-primary icon-cat-filter active" data-cat="all">All</button>
                            <button type="button" class="btn btn-xs btn-outline-secondary icon-cat-filter" data-cat="payment">Cash & Pay</button>
                            <button type="button" class="btn btn-xs btn-outline-secondary icon-cat-filter" data-cat="banking">Bank & Wallet</button>
                            <button type="button" class="btn btn-xs btn-outline-secondary icon-cat-filter" data-cat="crypto">Crypto & Currencies</button>
                            <button type="button" class="btn btn-xs btn-outline-secondary icon-cat-filter" data-cat="brands">Brands</button>
                        </div>
                    </div>
                </div>

                <!-- Icons Grid -->
                <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-6 g-2" id="iconLibraryGrid" style="max-height: 420px; overflow-y: auto;">
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
<div class="modal fade" id="previewInstructionsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title"><i class="ti tabler-info-circle me-2"></i>Instructions: <span id="prevModalMethodName" class="text-primary"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div id="prevModalContent" class="p-3 border rounded bg-light"></div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php
LayoutRenderer::renderFooter('reseller');
include __DIR__ . '/methods_scripts.php';
?>
