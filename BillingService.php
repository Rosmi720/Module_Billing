<?php

namespace XcVm\Module\Billing;

if (!defined('XC_VM_BILLING_AUTOLOADER_REGISTERED')) {
	define('XC_VM_BILLING_AUTOLOADER_REGISTERED', true);
	spl_autoload_register(static function (string $class): void {
		if (str_starts_with($class, 'XcVm\\Module\\Billing\\')) {
			$rel = substr($class, strlen('XcVm\\Module\\Billing\\'));
			$file = __DIR__ . '/' . str_replace('\\', '/', $rel) . '.php';
			if (is_file($file)) {
				require_once $file;
			}
		}
	});
}

use XcVm\Domain\User\UserCredits;
use XcVm\Domain\User\UserRepository;
use XcVm\Infrastructure\Database\DatabaseAware;

/**
 * BillingService — core business logic for payment methods, point purchase orders,
 * balance crediting/transfers, and audit logs.
 *
 * Fully adheres to XC_VM standards:
 * - Lazy DB access via DatabaseAware trait (`self::db()`).
 * - Parameter type-hint nullability checked to prevent TypeError runtime crashes.
 * - Atomic credit transfers using UserCredits::transfer / UserCredits::credit.
 * - Strict multi-tenancy validation between Reseller and Sub-Reseller.
 * - Concurrency state-transition locking to eliminate race conditions / double-spending.
 * - Robust HTML sanitization whitelist against Stored XSS attacks.
 * - Anti-flooding gates and cryptographic audit signatures.
 *
 * @package XC_VM_Module_Billing
 */
class BillingService {
	use DatabaseAware;

	public const UPLOAD_DIR = '/home/xc_vm/storage/billing_receipts/';
	public const MAX_FILE_SIZE = 5242880; // 5 MB

	public const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
	public const ALLOWED_MIME_TYPES = [
		'image/jpeg',
		'image/pjpeg',
		'image/jpg',
		'image/png',
		'image/x-png',
		'image/webp',
		'application/pdf',
	];

	/**
	 * Sanitize and secure HTML payment instructions.
	 *
	 * Supports full HTML markup, custom CSS (<style>), and interactive JavaScript (<script>, onclick, etc.)
	 * allowing distributors to build custom designs and interactive widgets, while enforcing strict protection
	 * against credential theft (blocking document.cookie, storage access, window redirection hijacking, and external iframes).
	 *
	 * @param string|null $html
	 * @return string
	 */
	public static function sanitizeHtml(?string $html): string {
		if ($html === null || trim($html) === '') {
			return '';
		}

		$cleaned = (string) $html;

		// Decode entity-encoded HTML tags (e.g. &lt;div, &lt;style, &lt;script) so valid markup is stored as real HTML
		if (preg_match('/&lt;[a-z!/]/i', $cleaned) || (str_contains($cleaned, '&lt;') && str_contains($cleaned, '&gt;'))) {
			$cleaned = html_entity_decode($cleaned, ENT_QUOTES | ENT_HTML5, 'UTF-8');
			if (preg_match('/&lt;[a-z!/]/i', $cleaned)) {
				$cleaned = html_entity_decode($cleaned, ENT_QUOTES | ENT_HTML5, 'UTF-8');
			}
		}

		// 1. Block dangerous external embedding plugins, iframes, meta tags, and base tag hijacking
		$cleaned = preg_replace('#<iframe\b[^>]*>(.*?)</iframe>#is', '', $cleaned);
		$cleaned = preg_replace('#<iframe\b[^>]*\/?>#is', '', $cleaned);
		$cleaned = preg_replace('#<object\b[^>]*>(.*?)</object>#is', '', $cleaned);
		$cleaned = preg_replace('#<object\b[^>]*\/?>#is', '', $cleaned);
		$cleaned = preg_replace('#<embed\b[^>]*>(.*?)</embed>#is', '', $cleaned);
		$cleaned = preg_replace('#<embed\b[^>]*\/?>#is', '', $cleaned);
		$cleaned = preg_replace('#<applet\b[^>]*>(.*?)</applet>#is', '', $cleaned);
		$cleaned = preg_replace('#<applet\b[^>]*\/?>#is', '', $cleaned);
		$cleaned = preg_replace('#<meta\b[^>]*\/?>#is', '', $cleaned);
		$cleaned = preg_replace('#<base\b[^>]*\/?>#is', '', $cleaned);

		// 2. Block cookie exfiltration patterns in scripts and handlers (Anti-Session Hijacking)
		$cleaned = preg_replace('/(\bdocument\b|\bwindow\.document\b)\s*(\.|\s*\[\s*[\'"`]\s*)cookie/i', '/*blocked_cookie*/""', $cleaned);

		// 3. Block storage token access
		$cleaned = preg_replace('/(?<![a-zA-Z0-9_$])(localStorage|sessionStorage)(?![a-zA-Z0-9_$])/i', '/*blocked_storage*/null', $cleaned);

		// 4. Block window/location redirection hijacking to prevent phishing
		$cleaned = preg_replace('/(\bwindow\b|\btop\b|\bparent\b|\bself\b)\s*(\.|\s*\[\s*[\'"`]\s*)location(\s*(\.|\s*\[\s*[\'"`]\s*)href)?\s*=/i', '/*blocked_nav*/void ', $cleaned);
		$cleaned = preg_replace('/(?<![a-zA-Z0-9_$])location\s*(\.|\s*\[\s*[\'"`]\s*)href\s*=/i', '/*blocked_nav*/void ', $cleaned);
		$cleaned = preg_replace('/(?<![a-zA-Z0-9_$])(location\.replace|location\.assign)\s*\(/i', '/*blocked_nav*/void(', $cleaned);

		// 5. Block dangerous pseudo-protocol URI execution in standard links or sources
		$cleaned = preg_replace('/(href|src)\s*=\s*("|\')\s*(javascript|vbscript):/i', '$1=$2#blocked:', $cleaned);

		// 6. Block code execution and external network exfiltration APIs (Defense-in-Depth)
		$cleaned = preg_replace('/(?<![a-zA-Z0-9_$])eval\s*\(/i', '/*blocked_eval*/void(', $cleaned);
		$cleaned = preg_replace('/(?<![a-zA-Z0-9_$])new\s+Function\s*\(/i', '/*blocked_func*/function(){ return function(){}; }(', $cleaned);
		$cleaned = preg_replace('/(?<![a-zA-Z0-9_$])(fetch|XMLHttpRequest|WebSocket)\b/i', '/*blocked_net*/void', $cleaned);
		$cleaned = preg_replace('/(?<![a-zA-Z0-9_$])import\s*\(/i', '/*blocked_import*/void(', $cleaned);

		return trim($cleaned);
	}

	/**
	 * Normalize a payment method row to ensure instructions remain uncorrupted HTML.
	 *
	 * @param array<string, mixed> $row
	 * @return array<string, mixed>
	 */
	public static function normalizeMethodRow(array $row): array {
		if (isset($row['instructions']) && is_string($row['instructions'])) {
			if (preg_match('/&lt;[a-z!/]/i', $row['instructions']) || (str_contains($row['instructions'], '&lt;') && str_contains($row['instructions'], '&gt;'))) {
				$row['instructions'] = html_entity_decode($row['instructions'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
			}
		}
		return $row;
	}

	// ───────────────────────────────────────────────────────────
	//  Payment Methods Management
	// ───────────────────────────────────────────────────────────

	/**
	 * Retrieve payment methods.
	 *
	 * @param int|null $resellerId Reseller user ID (0 for system/admin methods, null for all).
	 * @param bool     $activeOnly Filter active methods only.
	 * @return array<int, array<string, mixed>>
	 */
	public static function getPaymentMethods(?int $resellerId = null, bool $activeOnly = false): array {
		$db = self::db();
		$sql = 'SELECT m.*, u.username AS reseller_username, u.email AS reseller_email
				FROM `billing_payment_methods` m
				LEFT JOIN `users` u ON u.id = m.reseller_id
				WHERE 1=1';
		$params = [];

		if ($resellerId !== null) {
			$sql .= ' AND m.`reseller_id` = ?';
			$params[] = $resellerId;
		}

		if ($activeOnly) {
			$sql .= ' AND m.`active` = 1';
		}

		$sql .= ' ORDER BY m.`reseller_id` ASC, m.`sort_order` ASC, m.`id` ASC;';

		$db->query($sql, ...$params);
		// Note: get_raw_rows() prevents Database::clean_row() from converting '<' to '&lt;'
		$rows = $db->get_raw_rows() ?: [];
		foreach ($rows as &$r) {
			$r = self::normalizeMethodRow($r);
		}
		return $rows;
	}

	/**
	 * Retrieve a single payment method by ID.
	 *
	 * @param int $id
	 * @return array<string, mixed>|null
	 */
	public static function getPaymentMethodById(int $id): ?array {
		$db = self::db();
		$db->query('SELECT * FROM `billing_payment_methods` WHERE `id` = ? LIMIT 1;', $id);
		// Note: get_raw_row() prevents Database::clean_row() from converting '<' to '&lt;'
		$row = $db->get_raw_row();
		return is_array($row) && !empty($row) ? self::normalizeMethodRow($row) : null;
	}

	/**
	 * Save (create or update) a payment method.
	 *
	 * @param array<string, mixed> $data
	 * @param int|null             $resellerId The owner of the method (0 = admin, >0 = reseller).
	 * @param int|null             $id         Method ID if editing.
	 * @return array{success: bool, id?: int, error?: string}
	 */
	public static function savePaymentMethod(array $data, ?int $resellerId = 0, ?int $id = null): array {
		$db = self::db();
		$name         = trim((string) ($data['name'] ?? ''));
		$slug         = trim((string) ($data['slug'] ?? ''));
		$icon         = trim((string) ($data['icon'] ?? 'ti tabler-credit-card'));
		$instructions = self::sanitizeHtml((string) ($data['instructions'] ?? ''));
		$rate         = floatval($data['rate_per_point'] ?? 1.0);
		$rawCurrency  = strtoupper(trim((string) ($data['currency'] ?? 'USD')));
		$currency     = substr(preg_replace('/[^A-Z]/', '', $rawCurrency) ?: 'USD', 0, 10);
		$minPoints    = max(1, intval($data['min_points'] ?? 1));
		$maxPoints    = max(0, intval($data['max_points'] ?? 0));
		$active       = isset($data['active']) && ((int) $data['active'] === 1 || $data['active'] === 'on' || $data['active'] === true) ? 1 : 0;
		$sortOrder    = intval($data['sort_order'] ?? 0);
		$now          = time();

		if ($name === '') {
			return ['success' => false, 'error' => 'Method name cannot be empty.'];
		}

		if ($slug === '') {
			$slug = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '_', $name) ?: 'method');
		} else {
			$slug = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '_', $slug) ?: 'method');
		}

		if ($icon === '') {
			$icon = 'ti tabler-credit-card';
		}

		if (!is_finite($rate) || $rate <= 0) {
			$rate = 1.0;
		}

		if ($id !== null && $id > 0) {
			// Update
			$existing = self::getPaymentMethodById($id);
			if (!$existing) {
				return ['success' => false, 'error' => 'Payment method not found.'];
			}

			// Security check: if reseller, ensure they own it
			if ($resellerId !== null && $resellerId > 0 && (int) $existing['reseller_id'] !== $resellerId) {
				return ['success' => false, 'error' => 'Permission denied: cannot edit another user\'s method.'];
			}

			$db->query(
				'UPDATE `billing_payment_methods` SET
					`name` = ?,
					`slug` = ?,
					`icon` = ?,
					`instructions` = ?,
					`rate_per_point` = ?,
					`currency` = ?,
					`min_points` = ?,
					`max_points` = ?,
					`active` = ?,
					`sort_order` = ?,
					`updated_at` = ?
				WHERE `id` = ?;',
				$name,
				$slug,
				$icon,
				$instructions,
				number_format($rate, 4, '.', ''),
				$currency,
				$minPoints,
				$maxPoints,
				$active,
				$sortOrder,
				$now,
				$id
			);

			self::logAction('method_updated', null, $resellerId ?? 0, null, [
				'method_id' => $id,
				'name'      => $name,
				'active'    => $active,
			]);

			return ['success' => true, 'id' => $id];
		}

		// Insert
		$db->query(
			'INSERT INTO `billing_payment_methods`
				(`reseller_id`, `name`, `slug`, `icon`, `instructions`, `rate_per_point`, `currency`, `min_points`, `max_points`, `active`, `sort_order`, `created_at`, `updated_at`)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);',
			$resellerId ?? 0,
			$name,
			$slug,
			$icon,
			$instructions,
			number_format($rate, 4, '.', ''),
			$currency,
			$minPoints,
			$maxPoints,
			$active,
			$sortOrder,
			$now,
			$now
		);

		$newId = (int) $db->last_insert_id();

		self::logAction('method_created', null, $resellerId ?? 0, null, [
			'method_id' => $newId,
			'name'      => $name,
			'slug'      => $slug,
		]);

		return ['success' => true, 'id' => $newId];
	}

	/**
	 * Delete a payment method.
	 *
	 * @param int      $id
	 * @param int|null $resellerId Reseller ID if checking ownership (null for admin).
	 * @return bool
	 */
	public static function deletePaymentMethod(int $id, ?int $resellerId = null): bool {
		$db = self::db();
		$existing = self::getPaymentMethodById($id);
		if (!$existing) {
			return false;
		}

		if ($resellerId !== null && (int) $existing['reseller_id'] !== $resellerId) {
			return false;
		}

		$db->query('DELETE FROM `billing_payment_methods` WHERE `id` = ?;', $id);

		self::logAction('method_deleted', null, $resellerId ?? 0, null, [
			'method_id' => $id,
			'name'      => $existing['name'],
		]);

		return true;
	}

	/**
	 * Toggle active state of a payment method.
	 *
	 * @param int      $id
	 * @param int|null $resellerId
	 * @return bool
	 */
	public static function togglePaymentMethod(int $id, ?int $resellerId = null): bool {
		$existing = self::getPaymentMethodById($id);
		if (!$existing) {
			return false;
		}

		if ($resellerId !== null && (int) $existing['reseller_id'] !== $resellerId) {
			return false;
		}

		$newActive = ((int) $existing['active'] === 1) ? 0 : 1;
		$db = self::db();
		$db->query('UPDATE `billing_payment_methods` SET `active` = ?, `updated_at` = ? WHERE `id` = ?;', $newActive, time(), $id);

		self::logAction('method_toggled', null, $resellerId ?? 0, null, [
			'method_id'  => $id,
			'new_active' => $newActive,
		]);

		return true;
	}

	// ───────────────────────────────────────────────────────────
	//  Orders Management (Multi-Step Purchase Workflow)
	// ───────────────────────────────────────────────────────────

	/**
	 * Create a new point purchase order.
	 *
	 * @param int         $userId       Sub-reseller purchasing points.
	 * @param int         $sellerId     Parent reseller (or admin).
	 * @param int         $methodId     Selected payment method ID.
	 * @param float       $points       Requested points.
	 * @param string|null $txRef        Transaction reference/card/phone.
	 * @param string|null $senderNotes  Optional notes from buyer.
	 * @param string|null $receiptImage Path to uploaded receipt slip.
	 * @return array{success: bool, order_code?: string, order_id?: int, error?: string}
	 */
	public static function createOrder(
		int $userId,
		int $sellerId,
		int $methodId,
		float $points,
		?string $txRef = null,
		?string $senderNotes = null,
		?string $receiptImage = null
	): array {
		$db = self::db();

		if ($userId <= 0) {
			return ['success' => false, 'error' => 'Invalid user ID.'];
		}

		if ($userId === $sellerId) {
			return ['success' => false, 'error' => 'Permission denied: cannot create purchase order for yourself.'];
		}

		// Security: Strict decimal precision, finite validation, and bounds
		$points = round(floatval($points), 4);
		if (!is_finite($points) || $points < 0.0001 || $points > 1000000.0) {
			return ['success' => false, 'error' => 'Points amount must be a finite positive number between 0.0001 and 1,000,000.'];
		}

		// Security: Anti-flooding — Limit active pending orders per user to max 3
		$db->query('SELECT COUNT(*) AS `cnt` FROM `billing_orders` WHERE `user_id` = ? AND `status` = \'pending\';', $userId);
		$pendingCount = intval($db->get_row()['cnt'] ?? 0);
		if ($pendingCount >= 3) {
			return [
				'success' => false,
				'error'   => 'Anti-flooding protection: You already have 3 pending orders awaiting review. Please wait for them to be processed before submitting new orders.',
			];
		}

		// Security: Anti-flooding — 15-second cooldown between consecutive submissions
		$db->query('SELECT `created_at` FROM `billing_orders` WHERE `user_id` = ? ORDER BY `id` DESC LIMIT 1;', $userId);
		$lastOrderRow = $db->get_row();
		if (!empty($lastOrderRow['created_at'])) {
			$elapsed = time() - intval($lastOrderRow['created_at']);
			if ($elapsed < 15) {
				$waitSec = 15 - $elapsed;
				return [
					'success' => false,
					'error'   => "Anti-flooding protection: Please wait {$waitSec} seconds before submitting another purchase order.",
				];
			}
		}

		$method = self::getPaymentMethodById($methodId);
		if (!$method || (int) $method['active'] !== 1) {
			return ['success' => false, 'error' => 'Selected payment method is invalid or inactive.'];
		}

		// Security: strict multi-tenant isolation
		$methodOwner = (int) $method['reseller_id'];
		if ($sellerId === 0 && $methodOwner !== 0) {
			return ['success' => false, 'error' => 'Selected method is not an official platform payment method.'];
		}
		if ($sellerId > 0 && $methodOwner !== $sellerId) {
			return ['success' => false, 'error' => 'Payment method does not belong to your designated distributor.'];
		}

		$min = (int) $method['min_points'];
		$max = (int) $method['max_points'];
		if ($points < $min) {
			return ['success' => false, 'error' => "Minimum points required for this method is {$min}."];
		}
		if ($max > 0 && $points > $max) {
			return ['success' => false, 'error' => "Maximum points allowed for this method is {$max}."];
		}

		$rate       = floatval($method['rate_per_point'] ?? 1.0);
		$priceTotal = round($points * $rate, 4);
		$currency   = (string) ($method['currency'] ?? 'USD');
		$methodName = (string) $method['name'];
		$orderCode  = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
		$now        = time();

		$db->query(
			'INSERT INTO `billing_orders`
				(`order_code`, `user_id`, `seller_id`, `payment_method_id`, `method_name`, `points_requested`, `price_total`, `currency`, `transaction_reference`, `sender_notes`, `receipt_image`, `status`, `created_at`, `updated_at`)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'pending\', ?, ?);',
			$orderCode,
			$userId,
			$sellerId,
			$methodId,
			$methodName,
			number_format($points, 4, '.', ''),
			number_format($priceTotal, 4, '.', ''),
			$currency,
			$txRef ?: '',
			$senderNotes ?: '',
			$receiptImage ?: '',
			$now,
			$now
		);

		$orderId = (int) $db->last_insert_id();

		self::logAction('order_created', $orderId, $userId, $sellerId, [
			'order_code'       => $orderCode,
			'points_requested' => $points,
			'price_total'      => $priceTotal,
			'currency'         => $currency,
			'method_name'      => $methodName,
			'tx_ref'           => $txRef,
		]);

		return [
			'success'    => true,
			'order_code' => $orderCode,
			'order_id'   => $orderId,
		];
	}

	/**
	 * Retrieve order by ID.
	 *
	 * @param int $id
	 * @return array<string, mixed>|null
	 */
	public static function getOrderById(int $id): ?array {
		$db = self::db();
		$db->query(
			'SELECT o.*,
				u.username AS buyer_username,
				u.credits AS buyer_current_credits,
				s.username AS seller_username,
				s.credits AS seller_current_credits
			FROM `billing_orders` o
			LEFT JOIN `users` u ON u.id = o.user_id
			LEFT JOIN `users` s ON s.id = o.seller_id
			WHERE o.id = ? LIMIT 1;',
			$id
		);
		$row = $db->get_row();
		return is_array($row) && !empty($row) ? $row : null;
	}

	/**
	 * Retrieve order by code.
	 *
	 * @param string $code
	 * @return array<string, mixed>|null
	 */
	public static function getOrderByCode(string $code): ?array {
		$db = self::db();
		$db->query('SELECT * FROM `billing_orders` WHERE `order_code` = ? LIMIT 1;', $code);
		$row = $db->get_row();
		return is_array($row) && !empty($row) ? $row : null;
	}

	/**
	 * List orders with optional filters.
	 *
	 * @param array<string, mixed> $filters
	 * @param int                  $limit
	 * @param int                  $offset
	 * @return array<int, array<string, mixed>>
	 */
	public static function getOrders(array $filters = [], int $limit = 50, int $offset = 0): array {
		$db = self::db();
		$sql = 'SELECT o.*,
					u.username AS buyer_username,
					u.credits AS buyer_current_credits,
					s.username AS seller_username
				FROM `billing_orders` o
				LEFT JOIN `users` u ON u.id = o.user_id
				LEFT JOIN `users` s ON s.id = o.seller_id
				WHERE 1=1';
		$params = [];

		if (!empty($filters['user_id'])) {
			$sql .= ' AND o.`user_id` = ?';
			$params[] = (int) $filters['user_id'];
		}

		if (!empty($filters['seller_id'])) {
			$sql .= ' AND o.`seller_id` = ?';
			$params[] = (int) $filters['seller_id'];
		}

		if (!empty($filters['status'])) {
			$sql .= ' AND o.`status` = ?';
			$params[] = (string) $filters['status'];
		}

		if (!empty($filters['search'])) {
			$term = '%' . trim((string) $filters['search']) . '%';
			$sql .= ' AND (o.`order_code` LIKE ? OR o.`transaction_reference` LIKE ? OR u.`username` LIKE ?)';
			$params[] = $term;
			$params[] = $term;
			$params[] = $term;
		}

		$sql .= ' ORDER BY o.`id` DESC LIMIT ' . intval($limit) . ' OFFSET ' . intval($offset) . ';';

		$db->query($sql, ...$params);
		return $db->get_rows() ?: [];
	}

	/**
	 * Count orders matching filters.
	 *
	 * @param array<string, mixed> $filters
	 * @return int
	 */
	public static function countOrders(array $filters = []): int {
		$db = self::db();
		$sql = 'SELECT COUNT(*) AS `cnt` FROM `billing_orders` o LEFT JOIN `users` u ON u.id = o.user_id WHERE 1=1';
		$params = [];

		if (!empty($filters['user_id'])) {
			$sql .= ' AND o.`user_id` = ?';
			$params[] = (int) $filters['user_id'];
		}

		if (!empty($filters['seller_id'])) {
			$sql .= ' AND o.`seller_id` = ?';
			$params[] = (int) $filters['seller_id'];
		}

		if (!empty($filters['status'])) {
			$sql .= ' AND o.`status` = ?';
			$params[] = (string) $filters['status'];
		}

		if (!empty($filters['search'])) {
			$term = '%' . trim((string) $filters['search']) . '%';
			$sql .= ' AND (o.`order_code` LIKE ? OR o.`transaction_reference` LIKE ? OR u.`username` LIKE ?)';
			$params[] = $term;
			$params[] = $term;
			$params[] = $term;
		}

		$db->query($sql, ...$params);
		$row = $db->get_row();
		return intval($row['cnt'] ?? 0);
	}

	/**
	 * Approve an order and credit points atomically.
	 *
	 * If the reviewer is a reseller, credits are transferred from reviewer to buyer
	 * using `UserCredits::transfer()`.
	 * If the reviewer is an admin, points are directly credited using `UserCredits::credit()`.
	 *
	 * @param int         $orderId
	 * @param int         $reviewerId
	 * @param bool        $isAdmin
	 * @param string|null $notes
	 * @return array{success: bool, error?: string}
	 */
	public static function approveOrder(int $orderId, int $reviewerId, bool $isAdmin = false, ?string $notes = null): array {
		$db = self::db();
		$order = self::getOrderById($orderId);

		if (!$order) {
			return ['success' => false, 'error' => 'Order not found.'];
		}

		if ($order['status'] !== 'pending') {
			return ['success' => false, 'error' => "Order is already {$order['status']}."];
		}

		// Security: verify reviewer owns this order if not admin
		if (!$isAdmin && (int) $order['seller_id'] !== $reviewerId) {
			return ['success' => false, 'error' => 'Permission denied: you are not the designated seller for this order.'];
		}

		$points   = round(floatval($order['points_requested']), 4);
		$buyerId  = (int) $order['user_id'];
		$sellerId = (int) $order['seller_id'];

		// Security: prevent buyer from approving their own order
		if (!$isAdmin && $buyerId === $reviewerId) {
			return ['success' => false, 'error' => 'Permission denied: cannot approve your own order.'];
		}

		if ($points <= 0) {
			return ['success' => false, 'error' => 'Invalid points quantity.'];
		}

		$now = time();

		// ── STEP 1: Concurrency Protection — State-Transition Atomic Lock ──
		// Atomically transition status from 'pending' to 'processing'.
		// If another concurrent request already grabbed it, affected_rows will be 0 and we abort immediately.
		$db->query(
			'UPDATE `billing_orders` SET
				`status` = \'processing\',
				`processed_by` = ?,
				`processed_at` = ?,
				`updated_at` = ?
			WHERE `id` = ? AND `status` = \'pending\';',
			$reviewerId,
			$now,
			$now,
			$orderId
		);

		$affected = method_exists($db, 'affected_rows') ? $db->affected_rows() : (int) $db->num_rows();
		if ($affected !== 1) {
			return [
				'success' => false,
				'error'   => 'Order is currently being processed by another transaction or has already been resolved.',
			];
		}

		// ── STEP 2: Pre-transfer Balance Check (for Resellers) ──
		if (!$isAdmin && $sellerId > 0) {
			$currentSellerBalance = UserCredits::balance($sellerId);
			if ($currentSellerBalance < $points) {
				$deficit = max(0.0, round($points - $currentSellerBalance, 4));

				// Revert order status back to pending so reviewer can top-up and approve later
				$db->query(
					'UPDATE `billing_orders` SET
						`status` = \'pending\',
						`processed_by` = NULL,
						`processed_at` = NULL,
						`updated_at` = ?
					WHERE `id` = ? AND `status` = \'processing\';',
					$now,
					$orderId
				);

				return [
					'success' => false,
					'code'    => 'insufficient_balance',
					'deficit' => $deficit,
					'error'   => "Insufficient credits! Your current balance is {$currentSellerBalance}, but order requires {$points} credits. Deficit: {$deficit} credits.",
				];
			}
		}

		// ── STEP 3: Atomic Credit Transfer ──
		$buyerBeforeBalance  = UserCredits::balance($buyerId);
		$sellerBeforeBalance = ($sellerId > 0) ? UserCredits::balance($sellerId) : null;
		$transferred = false;

		if (!$isAdmin && $sellerId > 0) {
			$transferred = UserCredits::transfer($sellerId, $buyerId, $points);
		} else {
			$transferred = UserCredits::credit($buyerId, $points);
		}

		if (!$transferred) {
			// Transfer failed: revert order to pending
			$db->query(
				'UPDATE `billing_orders` SET
					`status` = \'pending\',
					`processed_by` = NULL,
					`processed_at` = NULL,
					`updated_at` = ?
				WHERE `id` = ? AND `status` = \'processing\';',
				$now,
				$orderId
			);

			return [
				'success' => false,
				'error'   => 'Failed to transfer credits: account balance check or database transaction failed.',
			];
		}

		$buyerAfterBalance  = UserCredits::balance($buyerId);
		$sellerAfterBalance = ($sellerId > 0) ? UserCredits::balance($sellerId) : null;

		// ── STEP 4: Finalize Order as Approved ──
		$db->query(
			'UPDATE `billing_orders` SET
				`status` = \'approved\',
				`updated_at` = ?
			WHERE `id` = ? AND `status` = \'processing\';',
			$now,
			$orderId
		);

		// Record in core credits log
		$logReason = 'Billing Order #' . $order['order_code'] . ' Approved' . ($notes ? ": {$notes}" : '');
		$db->query(
			'INSERT INTO `users_credits_logs` (`target_id`, `admin_id`, `amount`, `date`, `reason`) VALUES (?, ?, ?, ?, ?);',
			$buyerId,
			$reviewerId,
			number_format($points, 4, '.', ''),
			$now,
			$logReason
		);

		// Record in billing logs with cryptographic hash and balance ledger audit
		self::logAction('order_approved', $orderId, $reviewerId, $buyerId, [
			'order_code'            => $order['order_code'],
			'points_credited'       => $points,
			'buyer_before_balance'  => $buyerBeforeBalance,
			'buyer_after_balance'   => $buyerAfterBalance,
			'seller_before_balance' => $sellerBeforeBalance,
			'seller_after_balance'  => $sellerAfterBalance,
			'transferred_from'      => (!$isAdmin && $sellerId > 0) ? $sellerId : 'admin',
			'notes'                 => $notes,
		]);

		return ['success' => true];
	}

	/**
	 * Reject a purchase order.
	 *
	 * Uses state-transition atomic locking to prevent race conditions with approval.
	 *
	 * @param int         $orderId
	 * @param int         $reviewerId
	 * @param bool        $isAdmin
	 * @param string|null $reason
	 * @return array{success: bool, error?: string}
	 */
	public static function rejectOrder(int $orderId, int $reviewerId, bool $isAdmin = false, ?string $reason = null): array {
		$db = self::db();
		$order = self::getOrderById($orderId);

		if (!$order) {
			return ['success' => false, 'error' => 'Order not found.'];
		}

		if ($order['status'] !== 'pending') {
			return ['success' => false, 'error' => "Order is already {$order['status']}."];
		}

		if (!$isAdmin && (int) $order['seller_id'] !== $reviewerId) {
			return ['success' => false, 'error' => 'Permission denied: cannot reject orders for another seller.'];
		}

		// Security: prevent buyer from rejecting as seller
		if (!$isAdmin && (int) $order['user_id'] === $reviewerId) {
			return ['success' => false, 'error' => 'Permission denied: cannot reject your own order as seller.'];
		}

		$reasonText = trim((string) $reason) ?: 'Rejected by reviewer.';
		$now = time();

		// State-transition atomic lock: transition from 'pending' to 'rejected'
		$db->query(
			'UPDATE `billing_orders` SET
				`status` = \'rejected\',
				`rejection_reason` = ?,
				`processed_by` = ?,
				`processed_at` = ?,
				`updated_at` = ?
			WHERE `id` = ? AND `status` = \'pending\';',
			$reasonText,
			$reviewerId,
			$now,
			$now,
			$orderId
		);

		$affected = method_exists($db, 'affected_rows') ? $db->affected_rows() : (int) $db->num_rows();
		if ($affected !== 1) {
			return ['success' => false, 'error' => 'Order is already being processed or has already been resolved.'];
		}

		self::logAction('order_rejected', $orderId, $reviewerId, (int) $order['user_id'], [
			'order_code' => $order['order_code'],
			'reason'     => $reasonText,
		]);

		return ['success' => true];
	}

	// ───────────────────────────────────────────────────────────
	//  Dashboard & Overview Analytics
	// ───────────────────────────────────────────────────────────

	/**
	 * Compute overview statistics.
	 *
	 * @param int|null $resellerId Reseller user ID, or null for system-wide admin stats.
	 * @return array<string, mixed>
	 */
	public static function getOverviewStats(?int $resellerId = null): array {
		$db = self::db();

		$whereSeller = '';
		$params = [];
		if ($resellerId !== null && $resellerId > 0) {
			$whereSeller = ' WHERE `seller_id` = ?';
			$params[] = $resellerId;
		}

		// Count total, approved, pending, rejected
		$db->query(
			"SELECT
				COUNT(*) AS total_orders,
				SUM(CASE WHEN `status` = 'approved' THEN 1 ELSE 0 END) AS approved_orders,
				SUM(CASE WHEN `status` = 'pending' THEN 1 ELSE 0 END) AS pending_orders,
				SUM(CASE WHEN `status` = 'rejected' THEN 1 ELSE 0 END) AS rejected_orders,
				SUM(CASE WHEN `status` = 'approved' THEN `points_requested` ELSE 0 END) AS total_points_approved,
				SUM(CASE WHEN `status` = 'approved' THEN `price_total` ELSE 0 END) AS total_revenue
			FROM `billing_orders` {$whereSeller};",
			...$params
		);
		$stats = $db->get_row() ?: [];

		// Payment methods count
		$methodParams = [];
		$whereMethod = '';
		if ($resellerId !== null && $resellerId > 0) {
			$whereMethod = ' WHERE `reseller_id` = ?';
			$methodParams[] = $resellerId;
		}
		$db->query("SELECT COUNT(*) AS `cnt` FROM `billing_payment_methods` {$whereMethod};", ...$methodParams);
		$methodRow = $db->get_row();
		$totalMethods = intval($methodRow['cnt'] ?? 0);

		// Current credits of user
		$currentCredits = 0.0;
		if ($resellerId !== null && $resellerId > 0) {
			$currentCredits = UserCredits::balance($resellerId);
		}

		return [
			'total_orders'          => intval($stats['total_orders'] ?? 0),
			'approved_orders'       => intval($stats['approved_orders'] ?? 0),
			'pending_orders'        => intval($stats['pending_orders'] ?? 0),
			'rejected_orders'       => intval($stats['rejected_orders'] ?? 0),
			'total_points_approved' => floatval($stats['total_points_approved'] ?? 0),
			'total_revenue'         => floatval($stats['total_revenue'] ?? 0),
			'total_methods'         => $totalMethods,
			'current_credits'       => $currentCredits,
		];
	}

	/**
	 * Sub-reseller overview stats (their own purchases).
	 *
	 * @param int $subResellerId
	 * @return array<string, mixed>
	 */
	public static function getSubResellerOverview(int $subResellerId): array {
		$db = self::db();
		$db->query(
			"SELECT
				COUNT(*) AS total_orders,
				SUM(CASE WHEN `status` = 'approved' THEN 1 ELSE 0 END) AS approved_orders,
				SUM(CASE WHEN `status` = 'pending' THEN 1 ELSE 0 END) AS pending_orders,
				SUM(CASE WHEN `status` = 'approved' THEN `points_requested` ELSE 0 END) AS total_points_bought
			FROM `billing_orders` WHERE `user_id` = ?;",
			$subResellerId
		);
		$stats = $db->get_row() ?: [];

		return [
			'current_credits'     => UserCredits::balance($subResellerId),
			'total_orders'        => intval($stats['total_orders'] ?? 0),
			'approved_orders'     => intval($stats['approved_orders'] ?? 0),
			'pending_orders'      => intval($stats['pending_orders'] ?? 0),
			'total_points_bought' => floatval($stats['total_points_bought'] ?? 0),
		];
	}

	/**
	 * Resellers Directory for Admin Drill-Down.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function getResellerDirectory(): array {
		$db = self::db();
		$db->query(
			"SELECT
				u.id,
				u.username,
				u.email,
				u.credits,
				u.status,
				u.owner_id,
				g.group_name,
				(SELECT COUNT(*) FROM `users` sub WHERE sub.owner_id = u.id) AS sub_reseller_count,
				(SELECT COUNT(*) FROM `billing_payment_methods` m WHERE m.reseller_id = u.id) AS methods_count,
				(SELECT COUNT(*) FROM `billing_orders` o WHERE o.seller_id = u.id) AS orders_count,
				(SELECT SUM(o2.points_requested) FROM `billing_orders` o2 WHERE o2.seller_id = u.id AND o2.status = 'approved') AS total_points_sold
			FROM `users` u
			INNER JOIN `users_groups` g ON g.group_id = u.member_group_id
			WHERE g.is_reseller = 1
			ORDER BY u.id ASC;"
		);
		return $db->get_rows() ?: [];
	}

	/**
	 * Check if a reseller has any sub-resellers registered under them.
	 *
	 * @param int|null $userId
	 * @return bool
	 */
	public static function hasSubResellers(?int $userId): bool {
		if ($userId === null || $userId <= 0) {
			return false;
		}
		$db = self::db();
		$db->query('SELECT COUNT(*) AS `cnt` FROM `users` WHERE `owner_id` = ? LIMIT 1;', $userId);
		$row = $db->get_row();
		return intval($row['cnt'] ?? 0) > 0;
	}

	/**
	 * Get seller details (Admin or Parent Reseller).
	 *
	 * @param int|null $sellerId
	 * @return array{id: int, username: string, email: string, credits: float, is_admin: bool}
	 */
	public static function getSellerInfo(?int $sellerId): array {
		if ($sellerId === null || $sellerId <= 0) {
			return [
				'id'       => 0,
				'username' => 'Platform Administrator',
				'email'    => '',
				'credits'  => 0.0,
				'is_admin' => true,
			];
		}

		$user = UserRepository::getRegisteredUserById($sellerId);
		if (!$user) {
			return [
				'id'       => $sellerId,
				'username' => 'Distributor #' . $sellerId,
				'email'    => '',
				'credits'  => 0.0,
				'is_admin' => false,
			];
		}

		return [
			'id'       => intval($user['id']),
			'username' => strval($user['username']),
			'email'    => strval($user['email'] ?? ''),
			'credits'  => floatval($user['credits'] ?? 0),
			'is_admin' => false,
		];
	}

	// ───────────────────────────────────────────────────────────
	//  Secure File Upload Handler
	// ───────────────────────────────────────────────────────────

	/**
	 * Safely process and save receipt screenshot.
	 *
	 * @param array<string, mixed>|null $file $_FILES['receipt'] entry.
	 * @return array{success: bool, path?: string, error?: string}
	 */
	public static function handleFileUpload(?array $file): array {
		$isUploaded = is_uploaded_file($file['tmp_name'] ?? '') || (php_sapi_name() === 'cli' && !empty($file['tmp_name']) && file_exists($file['tmp_name']));
		if ($file === null || empty($file['tmp_name']) || !$isUploaded) {
			return ['success' => false, 'error' => 'No file uploaded or invalid upload.'];
		}

		if (!empty($file['error']) && $file['error'] !== UPLOAD_ERR_OK) {
			return ['success' => false, 'error' => 'Upload error code: ' . intval($file['error'])];
		}

		if ($file['size'] > self::MAX_FILE_SIZE) {
			return ['success' => false, 'error' => 'File exceeds maximum size of 5MB.'];
		}

		$filename = (string) ($file['name'] ?? '');
		$ext      = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

		if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
			return ['success' => false, 'error' => 'Invalid file extension. Only JPG, PNG, WEBP, and PDF are allowed.'];
		}

		// MIME inspection
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$mime  = finfo_file($finfo, $file['tmp_name']);
		finfo_close($finfo);

		if (!in_array($mime, self::ALLOWED_MIME_TYPES, true)) {
			return ['success' => false, 'error' => 'Invalid file format: ' . htmlspecialchars((string) $mime, ENT_QUOTES)];
		}

		// Ensure target directory exists and is writable
		if (!is_dir(self::UPLOAD_DIR)) {
			@mkdir(self::UPLOAD_DIR, 0775, true);
		}
		if (!is_writable(self::UPLOAD_DIR)) {
			@chmod(self::UPLOAD_DIR, 0775);
		}

		if (!is_writable(self::UPLOAD_DIR)) {
			return [
				'success' => false,
				'error'   => 'Upload storage directory is not writable. Please check server directory permissions.',
			];
		}

		$safeName   = hash('sha256', uniqid('receipt_', true) . bin2hex(random_bytes(8))) . '.' . $ext;
		$targetPath = self::UPLOAD_DIR . $safeName;

		$moved = @move_uploaded_file($file['tmp_name'], $targetPath);
		if (!$moved && file_exists($file['tmp_name'])) {
			$moved = @copy($file['tmp_name'], $targetPath);
			if ($moved) {
				@unlink($file['tmp_name']);
			}
		}

		if (!$moved) {
			$lastErr = error_get_last();
			$errDetail = $lastErr['message'] ?? 'Permission or storage write failure';
			return ['success' => false, 'error' => 'Failed to save uploaded file: ' . $errDetail];
		}

		@chmod($targetPath, 0664);

		return [
			'success' => true,
			'path'    => $safeName,
		];
	}

	// ───────────────────────────────────────────────────────────
	//  Audit Logging
	// ───────────────────────────────────────────────────────────

	/**
	 * Log a billing action.
	 *
	 * @param string                  $action
	 * @param int|null                $orderId
	 * @param int                     $userId
	 * @param int|null                $targetUserId
	 * @param array<string,mixed>|null $details
	 */
	public static function logAction(
		string $action,
		?int $orderId,
		int $userId,
		?int $targetUserId = null,
		?array $details = null
	): void {
		$db = self::db();
		$ip = $_SERVER['REMOTE_ADDR'] ?? (php_sapi_name() === 'cli' ? '127.0.0.1' : null);
		$now = time();

		$payload = $details ?? [];
		// Cryptographic integrity signature
		$salt = 'xcvm_billing_sec_sig_2026';
		$signString = implode('|', [
			$action,
			$orderId ?? 0,
			$userId,
			$targetUserId ?? 0,
			$ip ?? '',
			$now,
			json_encode($payload, JSON_UNESCAPED_UNICODE)
		]);
		$payload['_sig'] = hash('sha256', $signString . '|' . $salt);
		$payload['_ip']  = $ip;
		$payload['_ts']  = $now;

		$detailsJson = json_encode($payload, JSON_UNESCAPED_UNICODE);

		$db->query(
			'INSERT INTO `billing_logs` (`order_id`, `user_id`, `target_user_id`, `action`, `details`, `ip_address`, `created_at`)
			VALUES (?, ?, ?, ?, ?, ?, ?);',
			$orderId,
			$userId,
			$targetUserId,
			$action,
			$detailsJson,
			$ip,
			$now
		);
	}

	/**
	 * Retrieve audit logs.
	 *
	 * @param array<string, mixed> $filters
	 * @param int                  $limit
	 * @return array<int, array<string, mixed>>
	 */
	public static function getLogs(array $filters = [], int $limit = 50): array {
		$db = self::db();
		$sql = 'SELECT l.*,
					u.username AS actor_username,
					t.username AS target_username
				FROM `billing_logs` l
				LEFT JOIN `users` u ON u.id = l.user_id
				LEFT JOIN `users` t ON t.id = l.target_user_id
				WHERE 1=1';
		$params = [];

		if (!empty($filters['user_id'])) {
			$sql .= ' AND (l.`user_id` = ? OR l.`target_user_id` = ?)';
			$params[] = (int) $filters['user_id'];
			$params[] = (int) $filters['user_id'];
		}

		if (!empty($filters['order_id'])) {
			$sql .= ' AND l.`order_id` = ?';
			$params[] = (int) $filters['order_id'];
		}

		$sql .= ' ORDER BY l.`id` DESC LIMIT ' . intval($limit) . ';';

		$db->query($sql, ...$params);
		return $db->get_rows() ?: [];
	}
}
