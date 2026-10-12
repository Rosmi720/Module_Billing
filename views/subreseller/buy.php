<?php
use XcVm\Core\Util\LayoutRenderer;

/** @var array $methods */
/** @var array $sellerInfo */
/** @var int $sellerId */
/** @var bool $isDirectAdmin */
/** @var float $currentCredits */
global $rUserInfo;
?>
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1"><i class="ti tabler-coin me-2"></i>Buy Points (Recharge Account)</h4>
            <p class="text-muted mb-0">
                <?php if (!empty($isDirectAdmin)): ?>
                    <span class="badge bg-label-primary me-1"><i class="ti tabler-shield-lock me-1"></i>Direct Admin Order</span>
                    Purchasing points directly from the Platform Administrator.
                <?php else: ?>
                    <span class="badge bg-label-info me-1"><i class="ti tabler-user me-1"></i>Distributor: <?= htmlspecialchars($sellerInfo['username'] ?? 'Parent Reseller'); ?></span>
                    Purchasing points from your designated distributor.
                <?php endif; ?>
            </p>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="p-2 px-3 border rounded bg-light text-end">
                <small class="text-muted d-block">Your Current Balance</small>
                <span class="fw-bold text-success fs-5"><?= number_format(floatval($currentCredits ?? 0), 2); ?> credits</span>
            </div>
            <a href="billing" class="btn btn-outline-secondary">
                <i class="ti tabler-arrow-left me-1"></i> Back to Wallet
            </a>
        </div>
    </div>

    <?php if (empty($methods)): ?>
        <div class="card border-0 shadow-sm p-5 text-center">
            <div class="avatar avatar-xl bg-label-warning rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center shadow-sm">
                <i class="ti tabler-wallet-off fs-1 text-warning"></i>
            </div>
            <h4 class="fw-bold mb-2">No Payment Methods Configured</h4>
            <?php if (!empty($isDirectAdmin)): ?>
                <p class="text-muted max-w-500 mx-auto mb-4">No active system payment methods are currently published by the Administrator. Please reach out to platform support.</p>
            <?php else: ?>
                <p class="text-muted max-w-500 mx-auto mb-2">Your distributor <strong class="text-dark"><?= htmlspecialchars($sellerInfo['username'] ?? 'Parent Reseller'); ?></strong> has not configured any active payment methods yet.</p>
                <?php if (!empty($sellerInfo['email'])): ?>
                    <p class="small text-muted mb-4"><i class="ti tabler-mail me-1"></i>Distributor Email: <code><?= htmlspecialchars($sellerInfo['email']); ?></code></p>
                <?php else: ?>
                    <p class="small text-muted mb-4">Please contact your parent distributor directly to publish payment methods so you can recharge.</p>
                <?php endif; ?>
            <?php endif; ?>
            <div>
                <a href="billing" class="btn btn-outline-primary px-4"><i class="ti tabler-arrow-left me-1"></i> Return to Wallet</a>
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
                            <span class="fw-bold d-none d-md-inline">Choose Points</span>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="wizard-step p-2 border-bottom border-secondary border-3 opacity-50" id="stepIndicator2">
                            <span class="badge rounded-pill bg-secondary me-1">2</span>
                            <span class="fw-bold d-none d-md-inline">Payment Method</span>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="wizard-step p-2 border-bottom border-secondary border-3 opacity-50" id="stepIndicator3">
                            <span class="badge rounded-pill bg-secondary me-1">3</span>
                            <span class="fw-bold d-none d-md-inline">Transfer Info</span>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="wizard-step p-2 border-bottom border-secondary border-3 opacity-50" id="stepIndicator4">
                            <span class="badge rounded-pill bg-secondary me-1">4</span>
                            <span class="fw-bold d-none d-md-inline">Attach Proof</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body p-4 p-md-5">
                <form id="buyPointsForm" enctype="multipart/form-data">
                    <!-- ─── STEP 1: QUANTITY ─── -->
                    <div class="wizard-pane" id="paneStep1">
                        <div class="text-center mb-4">
                            <h4 class="fw-bold mb-1"><i class="ti tabler-stack me-2 text-primary"></i>Step 1: Enter Desired Points</h4>
                            <p class="text-muted">Specify how many points you want to add to your account balance.</p>
                        </div>

                        <div class="row justify-content-center">
                            <div class="col-md-7 col-lg-5">
                                <div class="mb-4 text-center">
                                    <label class="form-label fw-bold fs-5 mb-2">Points Quantity</label>
                                    <div class="input-group input-group-lg shadow-sm">
                                        <button class="btn btn-outline-secondary" type="button" id="btnMinusPoints"><i class="ti tabler-minus"></i></button>
                                        <input type="number" step="1" min="1" id="inputPoints" name="points" class="form-control text-center fw-bold fs-3 text-primary" value="50" required>
                                        <button class="btn btn-outline-secondary" type="button" id="btnPlusPoints"><i class="ti tabler-plus"></i></button>
                                    </div>
                                    <small class="text-muted mt-2 d-block">Points are added directly to your credits upon approval.</small>
                                </div>

                                <!-- Preset Quick Buttons -->
                                <div class="mb-4 text-center">
                                    <small class="text-muted d-block mb-2 fw-semibold">Quick Presets</small>
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
                                        Next: Select Payment Method <i class="ti tabler-arrow-right ms-1"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ─── STEP 2: CHOOSE PAYMENT METHOD ─── -->
                    <div class="wizard-pane d-none" id="paneStep2">
                        <div class="text-center mb-4">
                            <h4 class="fw-bold mb-1"><i class="ti tabler-wallet me-2 text-primary"></i>Step 2: Choose Payment Method</h4>
                            <p class="text-muted">Select how you will send payment to your distributor.</p>
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
                                                <span class="text-muted">Price Rate:</span>
                                                <span class="fw-bold text-success"><?= number_format(floatval($m['rate_per_point']), 2); ?> <?= htmlspecialchars((string) $m['currency']); ?> / pt</span>
                                            </div>
                                            <div class="d-flex justify-content-between small mt-1">
                                                <span class="text-muted">Min / Max:</span>
                                                <span><?= intval($m['min_points']); ?> - <?= (intval($m['max_points']) > 0) ? intval($m['max_points']) : '∞'; ?> pts</span>
                                            </div>
                                        </div>
                                        <div class="text-center mt-2">
                                            <span class="badge bg-secondary select-badge w-100 py-2"><i class="ti tabler-circle me-1"></i>Select this Method</span>
                                        </div>
                                        <textarea id="inst-raw-<?= (int) $m['id']; ?>" class="d-none"><?= htmlspecialchars((string) $m['instructions']); ?></textarea>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div id="methodErrorAlert" class="alert alert-danger d-none text-center"></div>

                        <div class="d-flex justify-content-between mt-4">
                            <button type="button" class="btn btn-outline-secondary px-4" id="btnBackToStep1">
                                <i class="ti tabler-arrow-left me-1"></i> Back
                            </button>
                            <button type="button" class="btn btn-primary px-4" id="btnGotoStep3" disabled>
                                Next: View Payment Details <i class="ti tabler-arrow-right ms-1"></i>
                            </button>
                        </div>
                    </div>

                    <!-- ─── STEP 3: PAYMENT DETAILS & INSTRUCTIONS ─── -->
                    <div class="wizard-pane d-none" id="paneStep3">
                        <div class="text-center mb-4">
                            <h4 class="fw-bold mb-1"><i class="ti tabler-info-square-rounded me-2 text-primary"></i>Step 3: Transfer Instructions & Account Details</h4>
                            <p class="text-muted">Send the exact amount below to your distributor using the provided details.</p>
                        </div>

                        <div class="row justify-content-center">
                            <div class="col-md-9 col-lg-8">
                                <!-- Order Calculation Summary Card -->
                                <div class="card bg-label-primary border-primary border-1 mb-4">
                                    <div class="card-body p-4">
                                        <div class="row align-items-center text-center text-md-start">
                                            <div class="col-md-6 mb-3 mb-md-0">
                                                <small class="text-muted d-block text-uppercase fw-semibold">Method Selected</small>
                                                <h4 class="mb-0 fw-bold" id="summaryMethodName"></h4>
                                                <small class="text-muted">Points requested: <strong id="summaryPoints"></strong></small>
                                            </div>
                                            <div class="col-md-6 text-md-end">
                                                <small class="text-muted d-block text-uppercase fw-semibold">Total Amount to Transfer</small>
                                                <h2 class="mb-0 fw-bold text-primary" id="summaryTotalCost"></h2>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Rich HTML Instructions from Reseller -->
                                <div class="card border border-2 shadow-sm mb-4">
                                    <div class="card-header bg-light border-bottom d-flex justify-content-between align-items-center">
                                        <span class="fw-bold"><i class="ti tabler-file-description me-1"></i>Distributor Account & Instructions</span>
                                        <button type="button" class="btn btn-xs btn-outline-secondary" id="btnCopyInstructions">
                                            <i class="ti tabler-copy me-1"></i> Copy
                                        </button>
                                    </div>
                                    <div class="card-body p-4" id="instructionsHtmlContent" style="min-height: 120px; font-size: 1.05rem;">
                                        <!-- Injected dynamically -->
                                    </div>
                                </div>

                                <div class="alert alert-info d-flex align-items-center">
                                    <i class="ti tabler-alert-circle fs-2 me-3"></i>
                                    <div>
                                        <strong>Important:</strong> After sending the payment, please copy your transaction ID / reference number and save a screenshot of the receipt for Step 4.
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between mt-4">
                                    <button type="button" class="btn btn-outline-secondary px-4" id="btnBackToStep2">
                                        <i class="ti tabler-arrow-left me-1"></i> Back
                                    </button>
                                    <button type="button" class="btn btn-success px-4" id="btnGotoStep4">
                                        I Have Transferred! Attach Proof <i class="ti tabler-arrow-right ms-1"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ─── STEP 4: ATTACH PROOF & SUBMIT ─── -->
                    <div class="wizard-pane d-none" id="paneStep4">
                        <div class="text-center mb-4">
                            <h4 class="fw-bold mb-1"><i class="ti tabler-checkup-list me-2 text-primary"></i>Step 4: Attach Transfer Proof</h4>
                            <p class="text-muted">Enter your transaction reference number and upload the receipt screenshot.</p>
                        </div>

                        <div class="row justify-content-center">
                            <div class="col-md-8 col-lg-7">
                                <div class="card border shadow-sm p-4 mb-4">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Transaction / Reference # / Sender Phone <span class="text-danger">*</span></label>
                                        <input type="text" name="tx_ref" id="inputTxRef" class="form-control form-control-lg font-monospace" placeholder="e.g. 987654321 / 01012345678 / TxHash" required>
                                        <small class="text-muted">Enter the transaction ID, receipt reference, or phone number you transferred from.</small>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Payment Receipt / Slip Screenshot <span class="text-danger">*</span></label>
                                        <input type="file" name="receipt" id="inputReceiptFile" class="form-control" accept="image/jpeg,image/png,image/webp,application/pdf" required>
                                        <small class="text-muted">Supported formats: JPG, PNG, WEBP, PDF (Max 5MB).</small>

                                        <!-- Image Preview Container -->
                                        <div id="receiptPreviewWrap" class="mt-3 text-center d-none">
                                            <div class="border rounded p-2 bg-light d-inline-block">
                                                <img id="receiptPreviewImg" src="" alt="Preview" class="img-fluid rounded" style="max-height: 200px;">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Additional Notes (Optional)</label>
                                        <textarea name="notes" id="inputNotes" rows="2" class="form-control" placeholder="Any extra information for your distributor..."></textarea>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between mt-4">
                                    <button type="button" class="btn btn-outline-secondary px-4" id="btnBackToStep3">
                                        <i class="ti tabler-arrow-left me-1"></i> Back
                                    </button>
                                    <button type="submit" class="btn btn-primary btn-lg px-5" id="btnSubmitFinalOrder">
                                        <i class="ti tabler-send me-1"></i> Submit Purchase Request
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
