<?php
use XcVm\Core\Util\LayoutRenderer;
use XcVm\Core\Localization\Translator;

/** @var array $methods */
/** @var array $sellerInfo */
/** @var int $sellerId */
/** @var bool $isDirectAdmin */
/** @var float $currentCredits */
global $rUserInfo;
$language = Translator::class;
?>
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1"><i class="ti tabler-coin me-2"></i><?= htmlspecialchars($language::get('billing_subreseller_buy_title')); ?></h4>
            <p class="text-muted mb-0">
                <?php if (!empty($isDirectAdmin)): ?>
                    <span class="badge bg-label-primary me-1"><i class="ti tabler-shield-lock me-1"></i><?= htmlspecialchars($language::get('billing_subreseller_direct_admin')); ?></span>
                    <?= htmlspecialchars($language::get('billing_subreseller_direct_admin_desc')); ?>
                <?php else: ?>
                    <span class="badge bg-label-info me-1"><i class="ti tabler-user me-1"></i><?= htmlspecialchars($language::get('billing_subreseller_distributor')); ?>: <?= htmlspecialchars($sellerInfo['username'] ?? 'Parent Reseller'); ?></span>
                    <?= htmlspecialchars($language::get('billing_subreseller_distributor_desc')); ?>
                <?php endif; ?>
            </p>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="p-2 px-3 border rounded bg-light text-end">
                <small class="text-muted d-block"><?= htmlspecialchars($language::get('billing_reseller_your_balance')); ?></small>
                <span class="fw-bold text-success fs-5"><?= number_format(floatval($currentCredits ?? 0), 2); ?> credits</span>
            </div>
            <a href="billing" class="btn btn-outline-secondary">
                <i class="ti tabler-arrow-left me-1"></i> <?= htmlspecialchars($language::get('billing_subreseller_back_wallet')); ?>
            </a>
        </div>
    </div>

    <?php if (empty($methods)): ?>
        <div class="card border-0 shadow-sm p-5 text-center">
            <div class="avatar avatar-xl bg-label-warning rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center shadow-sm">
                <i class="ti tabler-wallet-off fs-1 text-warning"></i>
            </div>
            <h4 class="fw-bold mb-2"><?= htmlspecialchars($language::get('billing_subreseller_no_methods_title')); ?></h4>
            <?php if (!empty($isDirectAdmin)): ?>
                <p class="text-muted max-w-500 mx-auto mb-4"><?= htmlspecialchars($language::get('billing_subreseller_no_methods_admin_desc')); ?></p>
            <?php else: ?>
                <p class="text-muted max-w-500 mx-auto mb-2"><?= htmlspecialchars($language::get('billing_subreseller_no_methods_reseller_desc_p1')); ?> <strong class="text-dark"><?= htmlspecialchars($sellerInfo['username'] ?? 'Parent Reseller'); ?></strong> <?= htmlspecialchars($language::get('billing_subreseller_no_methods_reseller_desc_p2')); ?></p>
                <?php if (!empty($sellerInfo['email'])): ?>
                    <p class="small text-muted mb-4"><i class="ti tabler-mail me-1"></i><?= htmlspecialchars($language::get('billing_subreseller_distributor_email')); ?>: <code><?= htmlspecialchars($sellerInfo['email']); ?></code></p>
                <?php else: ?>
                    <p class="small text-muted mb-4"><?= htmlspecialchars($language::get('billing_subreseller_no_methods_contact')); ?></p>
                <?php endif; ?>
            <?php endif; ?>
            <div>
                <a href="billing" class="btn btn-outline-primary px-4"><i class="ti tabler-arrow-left me-1"></i> <?= htmlspecialchars($language::get('billing_subreseller_back_wallet')); ?></a>
            </div>
        </div>
    <?php else: ?>
        <!-- Wizard Card -->
        <div class="card border-0 shadow-sm">
            <!-- Wizard Header / Steps Indicator -->
            <div class="card-header border-bottom bg-transparent p-4">
                <div class="row text-center g-2" id="wizardStepsIndicator">
                    <div class="col-3">
                        <div class="wizard-step active p-2 border-bottom border-primary border-3" id="stepIndicator1">
                            <span class="badge rounded-pill bg-primary me-1">1</span>
                            <span class="fw-bold d-none d-md-inline"><?= htmlspecialchars($language::get('billing_subreseller_step1_badge')); ?></span>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="wizard-step p-2 border-bottom border-secondary border-3 opacity-50" id="stepIndicator2">
                            <span class="badge rounded-pill bg-secondary me-1">2</span>
                            <span class="fw-bold d-none d-md-inline"><?= htmlspecialchars($language::get('billing_subreseller_step2_badge')); ?></span>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="wizard-step p-2 border-bottom border-secondary border-3 opacity-50" id="stepIndicator3">
                            <span class="badge rounded-pill bg-secondary me-1">3</span>
                            <span class="fw-bold d-none d-md-inline"><?= htmlspecialchars($language::get('billing_subreseller_step3_badge')); ?></span>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="wizard-step p-2 border-bottom border-secondary border-3 opacity-50" id="stepIndicator4">
                            <span class="badge rounded-pill bg-secondary me-1">4</span>
                            <span class="fw-bold d-none d-md-inline"><?= htmlspecialchars($language::get('billing_subreseller_step4_badge')); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body p-4 p-md-5">
                <form id="buyPointsForm" enctype="multipart/form-data">
                    <!-- ─── STEP 1: QUANTITY ─── -->
                    <div class="wizard-pane" id="paneStep1">
                        <div class="text-center mb-4">
                            <h4 class="fw-bold mb-1"><i class="ti tabler-stack me-2 text-primary"></i><?= htmlspecialchars($language::get('billing_subreseller_step1_title')); ?></h4>
                            <p class="text-muted"><?= htmlspecialchars($language::get('billing_subreseller_step1_desc')); ?></p>
                        </div>

                        <div class="row justify-content-center">
                            <div class="col-md-7 col-lg-5">
                                <div class="mb-4 text-center">
                                    <label class="form-label fw-bold fs-5 mb-2"><?= htmlspecialchars($language::get('billing_subreseller_points_qty')); ?></label>
                                    <div class="input-group input-group-lg shadow-sm">
                                        <button class="btn btn-outline-secondary" type="button" id="btnMinusPoints"><i class="ti tabler-minus"></i></button>
                                        <input type="number" step="1" min="1" id="inputPoints" name="points" class="form-control text-center fw-bold fs-3 text-primary" value="50" required>
                                        <button class="btn btn-outline-secondary" type="button" id="btnPlusPoints"><i class="ti tabler-plus"></i></button>
                                    </div>
                                    <small class="text-muted mt-2 d-block"><?= htmlspecialchars($language::get('billing_subreseller_points_auto_credited')); ?></small>
                                </div>

                                <!-- Preset Quick Buttons -->
                                <div class="mb-4 text-center">
                                    <small class="text-muted d-block mb-2 fw-semibold"><?= htmlspecialchars($language::get('billing_subreseller_quick_presets')); ?></small>
                                    <div class="d-flex justify-content-center flex-wrap gap-2">
                                        <button type="button" class="btn btn-outline-primary btn-sm preset-btn" data-val="10">+10</button>
                                        <button type="button" class="btn btn-outline-primary btn-sm preset-btn" data-val="25">+25</button>
                                        <button type="button" class="btn btn-outline-primary btn-sm preset-btn" data-val="50">+50</button>
                                        <button type="button" class="btn btn-outline-primary btn-sm preset-btn" data-val="100">+100</button>
                                        <button type="button" class="btn btn-outline-primary btn-sm preset-btn" data-val="250">+250</button>
                                        <button type="button" class="btn btn-outline-primary btn-sm preset-btn" data-val="500">+500</button>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end mt-4">
                                    <button type="button" class="btn btn-primary px-4" id="btnGotoStep2">
                                        <?= htmlspecialchars($language::get('billing_subreseller_next_select_method')); ?> <i class="ti tabler-arrow-right ms-1"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ─── STEP 2: CHOOSE PAYMENT METHOD ─── -->
                    <div class="wizard-pane d-none" id="paneStep2">
                        <div class="text-center mb-4">
                            <h4 class="fw-bold mb-1"><i class="ti tabler-wallet me-2 text-primary"></i><?= htmlspecialchars($language::get('billing_subreseller_step2_title')); ?></h4>
                            <p class="text-muted"><?= htmlspecialchars($language::get('billing_subreseller_step2_desc')); ?></p>
                        </div>

                        <input type="hidden" name="payment_method_id" id="selectedMethodId" value="">

                        <div class="row g-3 justify-content-center mb-4" id="methodsCardsGrid">
                            <?php foreach ($methods as $m): ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="card h-100 border method-card p-3 cursor-pointer shadow-sm transition-all"
                                         data-id="<?= (int) $m['id']; ?>"
                                         data-name="<?= htmlspecialchars((string) $m['name'], ENT_QUOTES); ?>"
                                         data-rate="<?= floatval($m['rate_per_point']); ?>"
                                         data-currency="<?= htmlspecialchars((string) $m['currency'], ENT_QUOTES); ?>"
                                         data-min="<?= intval($m['min_points']); ?>"
                                         data-max="<?= intval($m['max_points']); ?>"
                                         data-inst="<?= htmlspecialchars((string) $m['instructions'], ENT_QUOTES); ?>"
                                         style="cursor: pointer;">
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="avatar avatar-md bg-label-primary rounded-circle me-3 d-flex align-items-center justify-content-center">
                                                <i class="<?= htmlspecialchars((string) ($m['icon'] ?: 'ti tabler-credit-card')); ?> fs-3"></i>
                                            </div>
                                            <div>
                                                <h5 class="mb-0 fw-bold"><?= htmlspecialchars((string) $m['name']); ?></h5>
                                                <span class="badge bg-label-secondary font-monospace"><?= htmlspecialchars((string) $m['slug']); ?></span>
                                            </div>
                                        </div>
                                        <div class="p-2 bg-light rounded mt-2 mb-2">
                                            <div class="d-flex justify-content-between small">
                                                <span class="text-muted"><?= htmlspecialchars($language::get('billing_common_rate')); ?>:</span>
                                                <span class="fw-bold text-success"><?= number_format(floatval($m['rate_per_point']), 2); ?> <?= htmlspecialchars((string) $m['currency']); ?> / pt</span>
                                            </div>
                                            <div class="d-flex justify-content-between small mt-1">
                                                <span class="text-muted"><?= htmlspecialchars($language::get('billing_reseller_limits')); ?>:</span>
                                                <span><?= intval($m['min_points']); ?> - <?= (intval($m['max_points']) > 0) ? intval($m['max_points']) : '∞'; ?> pts</span>
                                            </div>
                                        </div>
                                        <div class="text-center mt-2">
                                            <span class="badge bg-secondary select-badge w-100 py-2"><i class="ti tabler-circle me-1"></i><?= htmlspecialchars($language::get('billing_subreseller_select_method_btn')); ?></span>
                                        </div>
                                        <textarea id="inst-raw-<?= (int) $m['id']; ?>" class="d-none"><?= htmlspecialchars((string) $m['instructions']); ?></textarea>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div id="methodErrorAlert" class="alert alert-danger d-none text-center"></div>

                        <div class="d-flex justify-content-between mt-4">
                            <button type="button" class="btn btn-outline-secondary px-4" id="btnBackToStep1">
                                <i class="ti tabler-arrow-left me-1"></i> <?= htmlspecialchars($language::get('billing_common_back')); ?>
                            </button>
                            <button type="button" class="btn btn-primary px-4" id="btnGotoStep3" disabled>
                                <?= htmlspecialchars($language::get('billing_subreseller_next_view_details')); ?> <i class="ti tabler-arrow-right ms-1"></i>
                            </button>
                        </div>
                    </div>

                    <!-- ─── STEP 3: PAYMENT DETAILS & INSTRUCTIONS ─── -->
                    <div class="wizard-pane d-none" id="paneStep3">
                        <div class="text-center mb-4">
                            <h4 class="fw-bold mb-1"><i class="ti tabler-info-square-rounded me-2 text-primary"></i><?= htmlspecialchars($language::get('billing_subreseller_step3_title')); ?></h4>
                            <p class="text-muted"><?= htmlspecialchars($language::get('billing_subreseller_step3_desc')); ?></p>
                        </div>

                        <div class="row justify-content-center">
                            <div class="col-md-9 col-lg-8">
                                <!-- Order Calculation Summary Card -->
                                <div class="card bg-label-primary border-primary border-1 mb-4">
                                    <div class="card-body p-4">
                                        <div class="row align-items-center text-center text-md-start">
                                            <div class="col-md-6 mb-3 mb-md-0">
                                                <small class="text-muted d-block text-uppercase fw-semibold"><?= htmlspecialchars($language::get('billing_subreseller_method_selected')); ?></small>
                                                <h4 class="mb-0 fw-bold" id="summaryMethodName"></h4>
                                                <small class="text-muted"><?= htmlspecialchars($language::get('billing_subreseller_points_requested_label')); ?>: <strong id="summaryPoints"></strong></small>
                                            </div>
                                            <div class="col-md-6 text-md-end">
                                                <small class="text-muted d-block text-uppercase fw-semibold"><?= htmlspecialchars($language::get('billing_subreseller_total_amount_transfer')); ?></small>
                                                <h2 class="mb-0 fw-bold text-primary" id="summaryTotalCost"></h2>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Rich HTML Instructions from Reseller -->
                                <div class="card border border-2 shadow-sm mb-4">
                                    <div class="card-header bg-light border-bottom d-flex justify-content-between align-items-center">
                                        <span class="fw-bold"><i class="ti tabler-file-description me-1"></i><?= htmlspecialchars($language::get('billing_subreseller_transfer_instructions')); ?></span>
                                        <button type="button" class="btn btn-xs btn-outline-secondary" id="btnCopyInstructions">
                                            <i class="ti tabler-copy me-1"></i> <?= htmlspecialchars($language::get('billing_common_copy')); ?>
                                        </button>
                                    </div>
                                    <div class="card-body p-4" id="instructionsHtmlContent" style="min-height: 120px; font-size: 1.05rem;">
                                        <!-- Injected dynamically -->
                                    </div>
                                </div>

                                <div class="alert alert-info d-flex align-items-center">
                                    <i class="ti tabler-alert-circle fs-2 me-3"></i>
                                    <div>
                                        <strong><?= htmlspecialchars($language::get('billing_common_important')); ?>:</strong> <?= htmlspecialchars($language::get('billing_subreseller_transfer_alert_msg')); ?>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between mt-4">
                                    <button type="button" class="btn btn-outline-secondary px-4" id="btnBackToStep2">
                                        <i class="ti tabler-arrow-left me-1"></i> <?= htmlspecialchars($language::get('billing_common_back')); ?>
                                    </button>
                                    <button type="button" class="btn btn-success px-4" id="btnGotoStep4">
                                        <?= htmlspecialchars($language::get('billing_subreseller_btn_transferred_proof')); ?> <i class="ti tabler-arrow-right ms-1"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ─── STEP 4: ATTACH PROOF & SUBMIT ─── -->
                    <div class="wizard-pane d-none" id="paneStep4">
                        <div class="text-center mb-4">
                            <h4 class="fw-bold mb-1"><i class="ti tabler-checkup-list me-2 text-primary"></i><?= htmlspecialchars($language::get('billing_subreseller_step4_title')); ?></h4>
                            <p class="text-muted"><?= htmlspecialchars($language::get('billing_subreseller_step4_desc')); ?></p>
                        </div>

                        <div class="row justify-content-center">
                            <div class="col-md-8 col-lg-7">
                                <div class="card border shadow-sm p-4 mb-4">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold"><?= htmlspecialchars($language::get('billing_subreseller_tx_ref_label')); ?> <span class="text-danger">*</span></label>
                                        <input type="text" name="tx_ref" id="inputTxRef" class="form-control form-control-lg font-monospace" placeholder="e.g. 987654321 / 01012345678 / TxHash" required>
                                        <small class="text-muted"><?= htmlspecialchars($language::get('billing_subreseller_tx_ref_help')); ?></small>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-bold"><?= htmlspecialchars($language::get('billing_subreseller_receipt_slip_label')); ?> <span class="text-danger">*</span></label>
                                        <input type="file" name="receipt" id="inputReceiptFile" class="form-control" accept="image/jpeg,image/png,image/webp,application/pdf" required>
                                        <small class="text-muted"><?= htmlspecialchars($language::get('billing_subreseller_receipt_formats_help')); ?></small>

                                        <!-- Image Preview Container -->
                                        <div id="receiptPreviewWrap" class="mt-3 text-center d-none">
                                            <div class="border rounded p-2 bg-light d-inline-block">
                                                <img id="receiptPreviewImg" src="" alt="Preview" class="img-fluid rounded" style="max-height: 200px;">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold"><?= htmlspecialchars($language::get('billing_subreseller_notes_optional')); ?></label>
                                        <textarea name="notes" id="inputNotes" rows="2" class="form-control" placeholder="<?= htmlspecialchars($language::get('billing_subreseller_notes_placeholder')); ?>"></textarea>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between mt-4">
                                    <button type="button" class="btn btn-outline-secondary px-4" id="btnBackToStep3">
                                        <i class="ti tabler-arrow-left me-1"></i> <?= htmlspecialchars($language::get('billing_common_back')); ?>
                                    </button>
                                    <button type="submit" class="btn btn-primary btn-lg px-5" id="btnSubmitFinalOrder">
                                        <i class="ti tabler-send me-1"></i> <?= htmlspecialchars($language::get('billing_subreseller_submit_order')); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
LayoutRenderer::renderFooter('reseller');
include __DIR__ . '/buy_scripts.php';
?>
