<?php

namespace XcVm\Module\Billing;

use XcVm\Core\Auth\Authorization;
use XcVm\Core\Http\RequestManager;
use XcVm\Core\Localization\Translator;
use XcVm\Core\Util\AdminHelpers;
use XcVm\Core\Util\LayoutRenderer;
use XcVm\Domain\User\UserRepository;

/**
 * BillingController — HTTP request and API handler for the Billing Module.
 *
 * Supports both Admin and Reseller / Sub-Reseller scopes with complete
 * multi-tenancy isolation and security checks.
 *
 * @package XC_VM_Module_Billing
 */
class BillingController {

	protected string $viewsPath;

	public function __construct() {
		$this->viewsPath = __DIR__ . '/views';
		BillingTranslator::init();
	}

	// ───────────────────────────────────────────────────────────
	//  Admin Scope Pages (GET)
	// ───────────────────────────────────────────────────────────

	/**
	 * Unified render method that wraps scope header, view, scope footer, and view_scripts.php
	 *
	 * @param string               $scope
	 * @param string               $title
	 * @param string               $view
	 * @param array<string, mixed> $vars
	 */
	protected function render(string $scope, string $title, string $view, array $vars = []): void {
		global $rUserInfo, $rSettings, $rMobile, $rServers, $rPermissions, $_STATUS;
		BillingTranslator::init();
		$language = Translator::class;
		extract($vars);

		LayoutRenderer::renderHeader($scope, ['_TITLE' => $title]);
		include $this->viewsPath . '/' . $view . '.php';
	}

	/**
	 * Admin Overview & Global Analytics.
	 */
	public function adminOverview(): void {
		global $rUserInfo, $rSettings, $rMobile;
		$_TITLE = Translator::get('billing_admin_overview_title');

		$stats = BillingService::getOverviewStats(null);
		$recentOrders = BillingService::getOrders([], 10, 0);

		$this->render('admin', $_TITLE, 'admin/overview', compact('stats', 'recentOrders'));
	}

	/**
	 * Admin: All Resellers Directory & Drill-down.
	 */
	public function adminResellers(): void {
		global $rUserInfo, $rSettings, $rMobile;
		$_TITLE = Translator::get('billing_admin_resellers_title');

		$resellers = BillingService::getResellerDirectory();

		$this->render('admin', $_TITLE, 'admin/resellers', compact('resellers'));
	}

	/**
	 * Admin: Master Orders Management.
	 */
	public function adminOrders(): void {
		global $rUserInfo, $rSettings, $rMobile;
		$_TITLE = Translator::get('billing_admin_orders_title');

		$status = RequestManager::get('status') ?: '';
		$search = RequestManager::get('search') ?: '';
		$sellerId = RequestManager::get('seller_id') ? intval(RequestManager::get('seller_id')) : null;

		$filters = [];
		if ($status !== '') $filters['status'] = $status;
		if ($search !== '') $filters['search'] = $search;
		if ($sellerId !== null && $sellerId > 0) $filters['seller_id'] = $sellerId;

		$totalCount = BillingService::countOrders($filters);
		$orders = BillingService::getOrders($filters, 50, 0);

		$this->render('admin', $_TITLE, 'admin/orders', compact('orders', 'totalCount', 'status', 'search', 'sellerId', 'filters'));
	}

	/**
	 * Admin: Payment Methods Manager (System & Reseller Methods).
	 */
	public function adminMethods(): void {
		global $rUserInfo, $rSettings, $rMobile;
		$_TITLE = Translator::get('billing_admin_methods_title');

		$allMethods = BillingService::getPaymentMethods(null);
		$adminMethods = [];
		$resellerMethods = [];
		foreach ($allMethods as $m) {
			if ((int) $m['reseller_id'] === 0) {
				$adminMethods[] = $m;
			} else {
				$resellerMethods[] = $m;
			}
		}

		$activeType = ($_GET['type'] ?? 'admin') === 'reseller' ? 'reseller' : 'admin';
		$methods = $allMethods;

		$this->render('admin', $_TITLE, 'admin/methods', compact('adminMethods', 'resellerMethods', 'activeType', 'methods', 'allMethods'));
	}

	/**
	 * Admin: Comprehensive Financial & Profit Analytics Hub.
	 */
	public function adminFinance(): void {
		global $rUserInfo, $rSettings, $rMobile;
		BillingTranslator::init();
		$_TITLE = Translator::get('billing_finance_admin_title');
		$GLOBALS['xmNewuiVendors'] = ['apexcharts'];

		$currency = RequestManager::get('currency') ?: null;
		$overview = BillingFinanceService::getFinanceOverview(null, null, null, $currency);
		$gateways = BillingFinanceService::getGatewaysBreakdown(null);
		$topPackages = BillingFinanceService::getTopPackagesBreakdown(null);
		$ledger = BillingFinanceService::getFinanceLedger([], 25, 0);
		$totalLedgerCount = BillingFinanceService::countFinanceLedger([]);
		$initialChart = BillingFinanceService::getTimeseriesData(null, 30, $currency);

		$this->render('admin', $_TITLE, 'admin/finance', compact('overview', 'gateways', 'topPackages', 'ledger', 'totalLedgerCount', 'currency', 'initialChart'));
	}

	// ───────────────────────────────────────────────────────────
	//  Reseller & Sub-Reseller Scope Pages (GET)
	// ───────────────────────────────────────────────────────────

	/**
	 * Reseller / Sub-Reseller Dashboard.
	 * Automatically routes based on whether user is top reseller or sub-reseller.
	 */
	public function resellerIndex(): void {
		global $rUserInfo, $rPermissions, $rSettings, $rMobile;
		$currentUser = $rUserInfo ?? [];
		$userId = intval($currentUser['id'] ?? 0);
		$ownerId = intval($currentUser['owner_id'] ?? 0);

		// Determine genuine distributor capability (user + group + owner check)
		$canManageSubResellers = BillingService::canManageSubResellers($userId, $currentUser, $rPermissions);

		if ($canManageSubResellers) {
			$_TITLE = Translator::get('billing_reseller_hub_title');
			$stats = BillingService::getOverviewStats($userId);
			$pendingOrders = BillingService::getOrders(['seller_id' => $userId, 'status' => 'pending'], 10, 0);
			$methods = BillingService::getPaymentMethods($userId);
			$myPurchases = BillingService::getOrders(['user_id' => $userId], 5, 0);

			$this->render('reseller', $_TITLE, 'reseller/overview', compact('stats', 'pendingOrders', 'methods', 'myPurchases', 'currentUser', 'userId', 'canManageSubResellers'));
		} else {
			$_TITLE = Translator::get('billing_subreseller_wallet_title');
			$stats = BillingService::getSubResellerOverview($userId);
			$orders = BillingService::getOrders(['user_id' => $userId], 10, 0);
			$parentMethods = BillingService::getPaymentMethods($ownerId, true);
			$sellerInfo = BillingService::getSellerInfo($ownerId);

			$this->render('reseller', $_TITLE, 'subreseller/overview', compact('stats', 'orders', 'parentMethods', 'sellerInfo', 'currentUser', 'userId', 'canManageSubResellers'));
		}
	}

	/**
	 * Reseller: My Payment Methods.
	 */
	public function resellerMethods(): void {
		global $rUserInfo, $rPermissions, $rSettings, $rMobile;
		$currentUser = $rUserInfo ?? [];
		$userId = intval($currentUser['id'] ?? 0);

		if (!BillingService::canManageSubResellers($userId, $currentUser, $rPermissions)) {
			header('Location: billing');
			exit;
		}

		$_TITLE = Translator::get('billing_reseller_nav_methods');
		$methods = BillingService::getPaymentMethods($userId);

		$this->render('reseller', $_TITLE, 'reseller/methods', compact('methods'));
	}

	/**
	 * Reseller: Incoming Orders from Sub-Resellers.
	 */
	public function resellerOrders(): void {
		global $rUserInfo, $rPermissions, $rSettings, $rMobile;
		$currentUser = $rUserInfo ?? [];
		$userId = intval($currentUser['id'] ?? 0);

		if (!BillingService::canManageSubResellers($userId, $currentUser, $rPermissions)) {
			header('Location: billing');
			exit;
		}

		$_TITLE = Translator::get('billing_reseller_nav_orders');
		$status = RequestManager::get('status') ?: '';
		$filters = ['seller_id' => $userId];
		if ($status !== '') $filters['status'] = $status;

		$orders = BillingService::getOrders($filters, 50, 0);

		$this->render('reseller', $_TITLE, 'reseller/orders', compact('orders', 'status', 'filters'));
	}

	/**
	 * Buy Points Wizard (Adaptive for both Main Reseller and Sub-Reseller).
	 */
	public function subresellerBuy(): void {
		global $rUserInfo, $rSettings, $rMobile;
		$currentUser = $rUserInfo ?? [];
		$userId = intval($currentUser['id'] ?? 0);
		$ownerId = intval($currentUser['owner_id'] ?? 0);
		$_TITLE = Translator::get('billing_subreseller_buy_title');

		// If user is a main reseller (owner_id <= 1), seller is Platform Admin (0).
		// If user is a sub-reseller (owner_id > 1), seller is their direct Parent Distributor (owner_id).
		$isDirectAdmin = ($ownerId <= 1);
		$sellerId = $isDirectAdmin ? 0 : $ownerId;
		$sellerInfo = BillingService::getSellerInfo($sellerId);
		$methods = BillingService::getPaymentMethods($sellerId, true);

		$currentCredits = \XcVm\Domain\User\UserCredits::balance($userId);

		$this->render('reseller', $_TITLE, 'subreseller/buy', compact('methods', 'sellerInfo', 'sellerId', 'isDirectAdmin', 'currentCredits', 'currentUser', 'userId', 'ownerId'));
	}

	/**
	 * Sub-Reseller: My Orders History.
	 */
	public function subresellerOrders(): void {
		global $rUserInfo, $rSettings, $rMobile;
		$currentUser = $rUserInfo ?? [];
		$userId = intval($currentUser['id'] ?? 0);
		$_TITLE = Translator::get('billing_subreseller_orders_history_title');

		$orders = BillingService::getOrders(['user_id' => $userId], 50, 0);

		$this->render('reseller', $_TITLE, 'subreseller/my_orders', compact('orders', 'currentUser', 'userId'));
	}

	/**
	 * Reseller: Financial Hub, Sales Ledger & Sub-Reseller Margins.
	 */
	public function resellerFinance(): void {
		global $rUserInfo, $rPermissions, $rSettings, $rMobile;
		BillingTranslator::init();
		$currentUser = $rUserInfo ?? [];
		$userId = intval($currentUser['id'] ?? 0);

		if ($userId <= 0) {
			header('Location: login');
			exit;
		}

		$_TITLE = Translator::get('billing_finance_reseller_title');
		$GLOBALS['xmNewuiVendors'] = ['apexcharts'];
		$currency = RequestManager::get('currency') ?: null;

		$overview = BillingFinanceService::getFinanceOverview($userId, null, null, $currency);
		$gateways = BillingFinanceService::getGatewaysBreakdown($userId);
		$topPackages = BillingFinanceService::getTopPackagesBreakdown($userId);
		$ledger = BillingFinanceService::getFinanceLedger(['owner_id' => $userId], 25, 0);
		$totalLedgerCount = BillingFinanceService::countFinanceLedger(['owner_id' => $userId]);
		$myStatement = BillingFinanceService::getUserDetailedStatement($userId, $userId);
		$initialChart = BillingFinanceService::getTimeseriesData($userId, 30, $currency);

		$this->render('reseller', $_TITLE, 'reseller/finance', compact('overview', 'gateways', 'topPackages', 'ledger', 'totalLedgerCount', 'myStatement', 'currentUser', 'userId', 'currency', 'initialChart'));
	}

	/**
	 * Secure Receipt Image Viewer.
	 * Streams the receipt file only to authorized users (Admin, Seller, or Buyer).
	 */
	public function viewReceipt(): void {
		global $rUserInfo;
		$user = $rUserInfo ?? [];
		$userId = intval($user['id'] ?? 0);
		$isAdmin = !empty($user['is_admin']) || (intval($user['member_group_id'] ?? 0) === 1);

		if ($userId <= 0) {
			http_response_code(401);
			echo 'Unauthorized session.';
			exit;
		}

		$orderId = intval(RequestManager::get('id') ?? 0);
		$order = BillingService::getOrderById($orderId);

		if (!$order || empty($order['receipt_image'])) {
			http_response_code(404);
			echo 'Receipt not found.';
			exit;
		}

		// Security authorization
		$buyerId = intval($order['user_id']);
		$sellerId = intval($order['seller_id']);

		if (!$isAdmin && $userId !== $buyerId && $userId !== $sellerId) {
			http_response_code(403);
			echo 'Access denied: you are not authorized to view this receipt.';
			exit;
		}

		$filename = basename((string) $order['receipt_image']);
		$filePath = BillingService::UPLOAD_DIR . $filename;

		$realDir = realpath(BillingService::UPLOAD_DIR);
		$realPath = realpath($filePath);
		if (!$realPath || !$realDir || !str_starts_with($realPath, $realDir . DIRECTORY_SEPARATOR)) {
			http_response_code(403);
			echo 'Access denied: invalid file path.';
			exit;
		}

		if (!file_exists($realPath) || !is_readable($realPath)) {
			http_response_code(404);
			echo 'Receipt file is missing from storage.';
			exit;
		}

		$ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
		$mimeMap = [
			'jpg'  => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'png'  => 'image/png',
			'webp' => 'image/webp',
			'pdf'  => 'application/pdf',
		];
		$contentType = $mimeMap[$ext] ?? 'application/octet-stream';

		header('Content-Type: ' . $contentType);
		header('Content-Length: ' . filesize($filePath));
		header('Content-Disposition: inline; filename="' . $filename . '"');
		header('Cache-Control: private, max-age=86400');
		readfile($filePath);
		exit;
	}

	// ───────────────────────────────────────────────────────────
	//  AJAX / API Endpoints (JSON)
	// ───────────────────────────────────────────────────────────

	/**
	 * API: Get payment methods for current context.
	 */
	public function apiGetMethods(): void {
		global $rUserInfo;
		$user = $rUserInfo ?? [];
		$userId = intval($user['id'] ?? 0);
		$isAdmin = !empty($user['is_admin']) || (intval($user['member_group_id'] ?? 0) === 1);

		if ($userId <= 0) {
			$this->json(['status' => 'error', 'message' => 'Unauthorized session.']);
		}

		$targetReseller = RequestManager::get('reseller_id');
		$ownerId = intval($user['owner_id'] ?? 0);

		// Prevent non-admins from scraping third-party resellers' payment methods
		if (!$isAdmin && $targetReseller !== null) {
			$requestedId = intval($targetReseller);
			$allowed = array_unique([$userId, $ownerId, 0]);
			if (!in_array($requestedId, $allowed, true)) {
				$this->json(['status' => 'error', 'message' => 'Permission denied: cannot query methods of another reseller.']);
			}
		}

		$resellerId = ($targetReseller !== null) ? intval($targetReseller) : ($isAdmin ? null : $userId);

		$methods = BillingService::getPaymentMethods($resellerId);
		$this->json(['status' => 'success', 'data' => $methods]);
	}

	/**
	 * API: Save a payment method.
	 */
	public function apiSaveMethod(): void {
		global $rUserInfo, $rPermissions;
		$user = $rUserInfo ?? [];
		$userId = intval($user['id'] ?? 0);
		$ownerId = intval($user['owner_id'] ?? 0);
		$isAdmin = !empty($user['is_admin']) || (intval($user['member_group_id'] ?? 0) === 1);

		if ($userId <= 0) {
			$this->json(['status' => 'error', 'message' => 'Unauthorized session.']);
		}

		$canManage = $isAdmin || BillingService::canManageSubResellers($userId, $user, $rPermissions);
		if (!$canManage) {
			$this->json(['status' => 'error', 'message' => 'Permission denied: sub-reseller distributor privileges required.']);
		}

		$id = RequestManager::get('id') ? intval(RequestManager::get('id')) : null;
		$resellerId = $isAdmin ? intval(RequestManager::get('reseller_id') ?? 0) : $userId;

		$res = BillingService::savePaymentMethod($_POST, $resellerId, $id);
		if ($res['success']) {
			$this->json(['status' => 'success', 'id' => $res['id']]);
		} else {
			$this->json(['status' => 'error', 'message' => $res['error'] ?? 'Save failed']);
		}
	}

	/**
	 * API: Delete a payment method.
	 */
	public function apiDeleteMethod(): void {
		global $rUserInfo, $rPermissions;
		$user = $rUserInfo ?? [];
		$userId = intval($user['id'] ?? 0);
		$ownerId = intval($user['owner_id'] ?? 0);
		$isAdmin = !empty($user['is_admin']) || (intval($user['member_group_id'] ?? 0) === 1);

		if ($userId <= 0) {
			$this->json(['status' => 'error', 'message' => 'Unauthorized session.']);
		}

		$canManage = $isAdmin || BillingService::canManageSubResellers($userId, $user, $rPermissions);
		if (!$canManage) {
			$this->json(['status' => 'error', 'message' => 'Permission denied: sub-reseller distributor privileges required.']);
		}

		$id = intval(RequestManager::get('id') ?? 0);
		$resellerCheck = $isAdmin ? null : $userId;

		if (BillingService::deletePaymentMethod($id, $resellerCheck)) {
			$this->json(['status' => 'success']);
		} else {
			$this->json(['status' => 'error', 'message' => 'Failed to delete payment method']);
		}
	}

	/**
	 * API: Toggle a payment method.
	 */
	public function apiToggleMethod(): void {
		global $rUserInfo, $rPermissions;
		$user = $rUserInfo ?? [];
		$userId = intval($user['id'] ?? 0);
		$ownerId = intval($user['owner_id'] ?? 0);
		$isAdmin = !empty($user['is_admin']) || (intval($user['member_group_id'] ?? 0) === 1);

		if ($userId <= 0) {
			$this->json(['status' => 'error', 'message' => 'Unauthorized session.']);
		}

		$canManage = $isAdmin || BillingService::canManageSubResellers($userId, $user, $rPermissions);
		if (!$canManage) {
			$this->json(['status' => 'error', 'message' => 'Permission denied: sub-reseller distributor privileges required.']);
		}

		$id = intval(RequestManager::get('id') ?? 0);
		$resellerCheck = $isAdmin ? null : $userId;

		if (BillingService::togglePaymentMethod($id, $resellerCheck)) {
			$this->json(['status' => 'success']);
		} else {
			$this->json(['status' => 'error', 'message' => 'Failed to toggle payment method']);
		}
	}

	/**
	 * API: Submit purchase order (Step 4 of wizard).
	 */
	public function apiSubmitOrder(): void {
		global $rUserInfo;
		$user = $rUserInfo ?? [];
		$userId = intval($user['id'] ?? 0);
		$ownerId = intval($user['owner_id'] ?? 0);

		if ($userId <= 0) {
			$this->json(['status' => 'error', 'message' => 'Unauthorized session.']);
		}

		$rawPoints = RequestManager::get('points');
		$points    = floatval($rawPoints ?? 0);
		$methodId  = intval(RequestManager::get('payment_method_id') ?? 0);
		$txRef     = trim((string) (RequestManager::get('tx_ref') ?? ''));
		$notes     = trim((string) (RequestManager::get('notes') ?? ''));

		if (!is_numeric($rawPoints) || !is_finite($points) || $points <= 0) {
			$this->json(['status' => 'error', 'message' => 'Please enter a valid points amount.']);
		}

		if ($methodId <= 0) {
			$this->json(['status' => 'error', 'message' => 'Please select a payment method.']);
		}

		// Handle receipt upload
		$receiptPath = null;
		if (!empty($_FILES['receipt']) && !empty($_FILES['receipt']['tmp_name'])) {
			$uploadRes = BillingService::handleFileUpload($_FILES['receipt']);
			if (!$uploadRes['success']) {
				$this->json(['status' => 'error', 'message' => $uploadRes['error'] ?? 'File upload error']);
			}
			$receiptPath = $uploadRes['path'];
		} else {
			$this->json(['status' => 'error', 'message' => 'Please attach your payment receipt or transfer proof screenshot.']);
		}

		// Seller is parent owner (or 0 for admin when owner_id <= 1)
		$sellerId = ($ownerId > 1) ? $ownerId : 0;

		$res = BillingService::createOrder($userId, $sellerId, $methodId, $points, $txRef, $notes, $receiptPath);
		if ($res['success']) {
			$this->json([
				'status'     => 'success',
				'order_code' => $res['order_code'],
				'order_id'   => $res['order_id'],
			]);
		} else {
			$this->json(['status' => 'error', 'message' => $res['error'] ?? 'Failed to submit order']);
		}
	}

	/**
	 * API: Get single order details (for review modal).
	 */
	public function apiGetOrder(): void {
		global $rUserInfo;
		$user = $rUserInfo ?? [];
		$userId = intval($user['id'] ?? 0);
		$isAdmin = !empty($user['is_admin']) || (intval($user['member_group_id'] ?? 0) === 1);

		if ($userId <= 0) {
			$this->json(['status' => 'error', 'message' => 'Unauthorized session.']);
		}

		$orderId = intval(RequestManager::get('id') ?? 0);
		$order = BillingService::getOrderById($orderId);

		if (!$order) {
			$this->json(['status' => 'error', 'message' => 'Order not found']);
		}

		// Permission check
		if (!$isAdmin && $userId !== intval($order['seller_id']) && $userId !== intval($order['user_id'])) {
			$this->json(['status' => 'error', 'message' => 'Access denied']);
		}

		// Add current balance of seller for confirmation UI
		$sellerCredits = \XcVm\Domain\User\UserCredits::balance(intval($order['seller_id']));
		$order['seller_credits_balance'] = $sellerCredits;

		$this->json(['status' => 'success', 'data' => $order]);
	}

	/**
	 * API: Approve order & credit points.
	 */
	public function apiApproveOrder(): void {
		global $rUserInfo;
		$user = $rUserInfo ?? [];
		$userId = intval($user['id'] ?? 0);
		$isAdmin = !empty($user['is_admin']) || (intval($user['member_group_id'] ?? 0) === 1);

		if ($userId <= 0) {
			$this->json(['status' => 'error', 'message' => 'Unauthorized session.']);
		}

		$orderId = intval(RequestManager::get('id') ?? 0);
		$notes = trim((string) (RequestManager::get('notes') ?? ''));

		$res = BillingService::approveOrder($orderId, $userId, $isAdmin, $notes ?: null);
		if ($res['success']) {
			$this->json(['status' => 'success', 'message' => 'Order approved and credits transferred successfully!']);
		} else {
			$this->json([
				'status'  => 'error',
				'code'    => $res['code'] ?? 'approval_failed',
				'deficit' => $res['deficit'] ?? 0,
				'message' => $res['error'] ?? 'Approval failed',
			]);
		}
	}

	/**
	 * API: Reject order.
	 */
	public function apiRejectOrder(): void {
		global $rUserInfo;
		$user = $rUserInfo ?? [];
		$userId = intval($user['id'] ?? 0);
		$isAdmin = !empty($user['is_admin']) || (intval($user['member_group_id'] ?? 0) === 1);

		if ($userId <= 0) {
			$this->json(['status' => 'error', 'message' => 'Unauthorized session.']);
		}

		$orderId = intval(RequestManager::get('id') ?? 0);
		$reason = trim((string) (RequestManager::get('reason') ?? ''));

		if ($reason === '') {
			$reason = 'Payment verification rejected.';
		}

		$res = BillingService::rejectOrder($orderId, $userId, $isAdmin, $reason);
		if ($res['success']) {
			$this->json(['status' => 'success', 'message' => 'Order has been rejected.']);
		} else {
			$this->json(['status' => 'error', 'message' => $res['error'] ?? 'Rejection failed']);
		}
	}

	/**
	 * API: Reseller drill-down data for Admin.
	 */
	public function apiDrilldown(): void {
		global $rUserInfo;
		$user = $rUserInfo ?? [];
		$userId = intval($user['id'] ?? 0);
		$isAdmin = !empty($user['is_admin']) || (intval($user['member_group_id'] ?? 0) === 1);

		if ($userId <= 0 || !$isAdmin) {
			$this->json(['status' => 'error', 'message' => 'Admin authorization required.']);
		}

		if (!$isAdmin) {
			$this->json(['status' => 'error', 'message' => 'Admin authorization required.']);
		}

		$resellerId = intval(RequestManager::get('reseller_id') ?: ($_GET['reseller_id'] ?? $_POST['reseller_id'] ?? 0));
		$reseller = UserRepository::getRegisteredUserById($resellerId);

		if (!$reseller) {
			$this->json(['status' => 'error', 'message' => 'Reseller not found.']);
		}

		$methods = BillingService::getPaymentMethods($resellerId);
		$orders  = BillingService::getOrders(['seller_id' => $resellerId], 25, 0);
		$stats   = BillingService::getOverviewStats($resellerId);
		$logs    = BillingService::getLogs(['user_id' => $resellerId], 20);

		$this->json([
			'status' => 'success',
			'data'   => [
				'reseller' => [
					'id'       => $reseller['id'],
					'username' => $reseller['username'],
					'email'    => $reseller['email'],
					'credits'  => $reseller['credits'],
					'status'   => $reseller['status'],
				],
				'stats'   => $stats,
				'methods' => $methods,
				'orders'  => $orders,
				'logs'    => $logs,
			],
		]);
	}

	/**
	 * API: Get filtered financial overview statistics.
	 */
	public function apiFinanceStats(): void {
		global $rUserInfo;
		$user = $rUserInfo ?? [];
		$userId = intval($user['id'] ?? 0);
		$isAdmin = !empty($user['is_admin']) || (intval($user['member_group_id'] ?? 0) === 1);

		if ($userId <= 0) {
			$this->json(['status' => 'error', 'message' => 'Unauthorized session.']);
		}

		$startDate = RequestManager::get('start_date') ? intval(RequestManager::get('start_date')) : null;
		$endDate = RequestManager::get('end_date') ? intval(RequestManager::get('end_date')) : null;
		$currency = RequestManager::get('currency') ?: null;

		$targetReseller = RequestManager::get('reseller_id');
		if ($isAdmin) {
			$resellerId = ($targetReseller !== null && $targetReseller !== '') ? intval($targetReseller) : null;
		} else {
			$resellerId = $userId;
		}

		$overview = BillingFinanceService::getFinanceOverview($resellerId, $startDate, $endDate, $currency);
		$gateways = BillingFinanceService::getGatewaysBreakdown($resellerId, $startDate, $endDate);
		$topPackages = BillingFinanceService::getTopPackagesBreakdown($resellerId, $startDate, $endDate);

		$this->json([
			'status' => 'success',
			'data'   => [
				'overview'     => $overview,
				'gateways'     => $gateways,
				'top_packages' => $topPackages,
			],
		]);
	}

	/**
	 * API: Get timeseries daily chart data for finance graphs.
	 */
	public function apiFinanceChart(): void {
		global $rUserInfo;
		$user = $rUserInfo ?? [];
		$userId = intval($user['id'] ?? 0);
		$isAdmin = !empty($user['is_admin']) || (intval($user['member_group_id'] ?? 0) === 1);

		if ($userId <= 0) {
			$this->json(['status' => 'error', 'message' => 'Unauthorized session.']);
		}

		$days = RequestManager::get('days') ? intval(RequestManager::get('days')) : 30;
		$currency = RequestManager::get('currency') ?: null;

		$targetReseller = RequestManager::get('reseller_id');
		$resellerId = ($isAdmin && $targetReseller !== null && $targetReseller !== '') ? intval($targetReseller) : ($isAdmin ? null : $userId);

		$chart = BillingFinanceService::getTimeseriesData($resellerId, $days, $currency);
		$this->json(['status' => 'success', 'data' => $chart]);
	}

	/**
	 * API: Get paginated financial ledger rows with filters.
	 */
	public function apiFinanceLedger(): void {
		global $rUserInfo;
		$user = $rUserInfo ?? [];
		$userId = intval($user['id'] ?? 0);
		$isAdmin = !empty($user['is_admin']) || (intval($user['member_group_id'] ?? 0) === 1);

		if ($userId <= 0) {
			$this->json(['status' => 'error', 'message' => 'Unauthorized session.']);
		}

		$filters = [];
		if (!$isAdmin) {
			$filters['owner_id'] = $userId;
		} else {
			$ownerId = RequestManager::get('owner_id');
			if ($ownerId !== null && $ownerId !== '') {
				$filters['owner_id'] = intval($ownerId);
			}
		}

		$search = RequestManager::get('search');
		if ($search !== null && trim($search) !== '') {
			$filters['search'] = trim($search);
		}

		$groupType = RequestManager::get('group_type');
		if ($groupType !== null && trim($groupType) !== '') {
			$filters['group_type'] = trim($groupType);
		}

		$limit = RequestManager::get('limit') ? intval(RequestManager::get('limit')) : 25;
		$page = RequestManager::get('page') ? intval(RequestManager::get('page')) : 1;
		$offset = max(0, ($page - 1) * $limit);

		$rows = BillingFinanceService::getFinanceLedger($filters, $limit, $offset);
		$total = BillingFinanceService::countFinanceLedger($filters);

		$this->json([
			'status' => 'success',
			'data'   => $rows,
			'total'  => $total,
			'page'   => $page,
			'limit'  => $limit,
		]);
	}

	/**
	 * API: Get itemized statement of account for a specific user.
	 */
	public function apiFinanceStatement(): void {
		global $rUserInfo;
		$user = $rUserInfo ?? [];
		$userId = intval($user['id'] ?? 0);
		$isAdmin = !empty($user['is_admin']) || (intval($user['member_group_id'] ?? 0) === 1);

		if ($userId <= 0) {
			$this->json(['status' => 'error', 'message' => 'Unauthorized session.']);
		}

		$targetUserId = intval(RequestManager::get('user_id') ?: 0);
		if ($targetUserId <= 0) {
			$targetUserId = $userId;
		}

		$scopeResellerId = $isAdmin ? null : $userId;
		$statement = BillingFinanceService::getUserDetailedStatement($targetUserId, $scopeResellerId);

		if (!$statement) {
			$this->json(['status' => 'error', 'message' => 'User statement not found or unauthorized access.']);
		}

		$this->json(['status' => 'success', 'data' => $statement]);
	}

	/**
	 * API: Export financial ledger data as CSV download.
	 */
	public function apiFinanceExport(): void {
		global $rUserInfo;
		$user = $rUserInfo ?? [];
		$userId = intval($user['id'] ?? 0);
		$isAdmin = !empty($user['is_admin']) || (intval($user['member_group_id'] ?? 0) === 1);

		if ($userId <= 0) {
			http_response_code(401);
			echo 'Unauthorized session.';
			exit;
		}

		$filters = [];
		if (!$isAdmin) {
			$filters['owner_id'] = $userId;
		}

		$search = RequestManager::get('search');
		if ($search !== null && trim($search) !== '') {
			$filters['search'] = trim($search);
		}

		$csv = BillingFinanceService::exportLedgerCsv($filters);

		if (ob_get_length()) {
			ob_clean();
		}
		header('Content-Type: text/csv; charset=UTF-8');
		header('Content-Disposition: attachment; filename="finance_ledger_' . date('Ymd_His') . '.csv"');
		header('Pragma: no-cache');
		header('Expires: 0');
		echo $csv;
		exit;
	}

	// ───────────────────────────────────────────────────────────
	//  Internal Helpers
	// ───────────────────────────────────────────────────────────

	protected function json(array $data): never {
		if (ob_get_length()) {
			ob_clean();
		}
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
		exit;
	}
}
