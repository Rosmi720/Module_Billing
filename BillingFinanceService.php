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

use XcVm\Infrastructure\Database\DatabaseAware;

/**
 * BillingFinanceService — Financial accounting engine, profit calculations,
 * multi-currency aggregation, and user ledger statements.
 *
 * Compliant with XC_VM architecture:
 * - Lazy DB access via DatabaseAware (`self::db()`).
 * - Null-safe scalar handling to prevent TypeError crashes.
 * - Strict multi-tenant isolation between Admin and Reseller.
 * - Optimized indexing on created_at, seller_id, user_id, and status.
 *
 * @package XC_VM_Module_Billing
 */
class BillingFinanceService {
	use DatabaseAware;

	/**
	 * Get comprehensive financial overview stats (KPIs).
	 *
	 * @param int|null    $resellerId Context: null for Global/Admin, int for specific Reseller
	 * @param int|null    $startDate  Unix timestamp start
	 * @param int|null    $endDate    Unix timestamp end
	 * @param string|null $currency   Optional currency filter (e.g. 'USD')
	 * @return array<string, mixed>
	 */
	public static function getFinanceOverview(?int $resellerId = null, ?int $startDate = null, ?int $endDate = null, ?string $currency = null): array {
		$db = self::db();

		// Common date constraints
		$orderDateClause = '';
		$orderParams = [];
		if ($startDate !== null && $startDate > 0) {
			$orderDateClause .= ' AND o.created_at >= ?';
			$orderParams[] = $startDate;
		}
		if ($endDate !== null && $endDate > 0) {
			$orderDateClause .= ' AND o.created_at <= ?';
			$orderParams[] = $endDate;
		}
		if ($currency !== null && $currency !== '' && $currency !== 'all') {
			$orderDateClause .= ' AND o.currency = ?';
			$orderParams[] = strtoupper($currency);
		}

		if ($resellerId === null || $resellerId === 0) {
			// ─────────────────────────────────────────────────────────────
			// ADMIN / GLOBAL FINANCIAL OVERVIEW
			// ─────────────────────────────────────────────────────────────

			// 1. Approved Inflow by Currency (Realized Cash)
			$sqlApproved = "SELECT
					o.currency,
					COUNT(o.id) AS orders_count,
					COALESCE(SUM(o.price_total), 0) AS total_revenue,
					COALESCE(SUM(o.points_requested), 0) AS total_points
				FROM `billing_orders` o
				WHERE o.status = 'approved'" . $orderDateClause . "
				GROUP BY o.currency";
			$db->query($sqlApproved, ...$orderParams);
			$approvedRows = $db->get_rows() ?: [];

			$revenueByCurrency = [];
			$totalPointsMinted = 0.0;
			$totalApprovedOrders = 0;
			foreach ($approvedRows as $row) {
				$curr = strtoupper((string) ($row['currency'] ?? 'USD'));
				$rev = floatval($row['total_revenue'] ?? 0);
				$pts = floatval($row['total_points'] ?? 0);
				$cnt = intval($row['orders_count'] ?? 0);

				$revenueByCurrency[$curr] = [
					'currency'     => $curr,
					'revenue'      => $rev,
					'points'       => $pts,
					'orders_count' => $cnt,
				];
				$totalPointsMinted += $pts;
				$totalApprovedOrders += $cnt;
			}

			// 2. Pending Inflow by Currency (Receivables in review)
			$sqlPending = "SELECT
					o.currency,
					COUNT(o.id) AS orders_count,
					COALESCE(SUM(o.price_total), 0) AS total_pending,
					COALESCE(SUM(o.points_requested), 0) AS total_points
				FROM `billing_orders` o
				WHERE o.status = 'pending'" . $orderDateClause . "
				GROUP BY o.currency";
			$db->query($sqlPending, ...$orderParams);
			$pendingRows = $db->get_rows() ?: [];

			$pendingByCurrency = [];
			$totalPendingOrders = 0;
			foreach ($pendingRows as $row) {
				$curr = strtoupper((string) ($row['currency'] ?? 'USD'));
				$amt = floatval($row['total_pending'] ?? 0);
				$pts = floatval($row['total_points'] ?? 0);
				$cnt = intval($row['orders_count'] ?? 0);

				$pendingByCurrency[$curr] = [
					'currency'     => $curr,
					'amount'       => $amt,
					'points'       => $pts,
					'orders_count' => $cnt,
				];
				$totalPendingOrders += $cnt;
			}

			// 3. Rejected Orders Count
			$sqlRejected = "SELECT COUNT(o.id) AS cnt FROM `billing_orders` o WHERE o.status = 'rejected'" . $orderDateClause;
			$db->query($sqlRejected, ...$orderParams);
			$rejectedRow = $db->get_row();
			$totalRejectedOrders = intval($rejectedRow['cnt'] ?? 0);

			// 4. In-System Circulating Points Liability (All unspent credits in wallets)
			$db->query("SELECT COALESCE(SUM(credits), 0) AS circulating_credits, COUNT(id) AS active_wallets FROM `users` WHERE credits > 0");
			$circRow = $db->get_row();
			$circulatingCredits = floatval($circRow['circulating_credits'] ?? 0);
			$activeWalletsCount = intval($circRow['active_wallets'] ?? 0);

			// 5. Total Point Consumption (Lines, MAGs, Renewals from users_logs)
			$usageDateClause = '';
			$usageParams = [];
			if ($startDate !== null && $startDate > 0) {
				$usageDateClause .= ' AND date >= ?';
				$usageParams[] = $startDate;
			}
			if ($endDate !== null && $endDate > 0) {
				$usageDateClause .= ' AND date <= ?';
				$usageParams[] = $endDate;
			}
			$sqlUsage = "SELECT
					COUNT(id) AS actions_count,
					COALESCE(SUM(cost), 0) AS total_points_consumed
				FROM `users_logs`
				WHERE cost > 0" . $usageDateClause;
			$db->query($sqlUsage, ...$usageParams);
			$usageRow = $db->get_row();
			$pointsConsumed = floatval($usageRow['total_points_consumed'] ?? 0);
			$consumptionActionsCount = intval($usageRow['actions_count'] ?? 0);

			// Conversion Rate
			$totalProcessed = $totalApprovedOrders + $totalRejectedOrders;
			$approvalRate = $totalProcessed > 0 ? round(($totalApprovedOrders / $totalProcessed) * 100, 1) : 100.0;

			return [
				'scope'                    => 'admin',
				'revenue_by_currency'      => $revenueByCurrency,
				'pending_by_currency'      => $pendingByCurrency,
				'total_points_minted'      => $totalPointsMinted,
				'total_points_consumed'    => $pointsConsumed,
				'circulating_liability'    => $circulatingCredits,
				'active_wallets_count'     => $activeWalletsCount,
				'approved_orders_count'    => $totalApprovedOrders,
				'pending_orders_count'     => $totalPendingOrders,
				'rejected_orders_count'    => $totalRejectedOrders,
				'approval_rate'            => $approvalRate,
				'consumption_events_count' => $consumptionActionsCount,
			];
		}

		// ─────────────────────────────────────────────────────────────
		// RESELLER FINANCIAL OVERVIEW
		// ─────────────────────────────────────────────────────────────
		$sellerParams = array_merge([$resellerId], $orderParams);

		// 1. Sales to Sub-Resellers (Inflow where reseller is seller)
		$sqlSellerOrders = "SELECT
				o.currency,
				COUNT(o.id) AS orders_count,
				COALESCE(SUM(o.price_total), 0) AS total_revenue,
				COALESCE(SUM(o.points_requested), 0) AS total_points
			FROM `billing_orders` o
			WHERE o.seller_id = ? AND o.status = 'approved'" . $orderDateClause . "
			GROUP BY o.currency";
		$db->query($sqlSellerOrders, ...$sellerParams);
		$sellerRows = $db->get_rows() ?: [];

		$salesByCurrency = [];
		$totalPointsSoldToSubs = 0.0;
		$totalSubOrdersApproved = 0;
		foreach ($sellerRows as $row) {
			$curr = strtoupper((string) ($row['currency'] ?? 'USD'));
			$rev = floatval($row['total_revenue'] ?? 0);
			$pts = floatval($row['total_points'] ?? 0);
			$cnt = intval($row['orders_count'] ?? 0);

			$salesByCurrency[$curr] = [
				'currency'     => $curr,
				'revenue'      => $rev,
				'points'       => $pts,
				'orders_count' => $cnt,
			];
			$totalPointsSoldToSubs += $pts;
			$totalSubOrdersApproved += $cnt;
		}

		// 2. Pending Sub-Reseller Orders
		$sqlPendingSubs = "SELECT
				o.currency,
				COUNT(o.id) AS orders_count,
				COALESCE(SUM(o.price_total), 0) AS total_pending,
				COALESCE(SUM(o.points_requested), 0) AS total_points
			FROM `billing_orders` o
			WHERE o.seller_id = ? AND o.status = 'pending'" . $orderDateClause . "
			GROUP BY o.currency";
		$db->query($sqlPendingSubs, ...$sellerParams);
		$pendingSubsRows = $db->get_rows() ?: [];

		$pendingSubsByCurrency = [];
		$totalPendingSubsOrders = 0;
		foreach ($pendingSubsRows as $row) {
			$curr = strtoupper((string) ($row['currency'] ?? 'USD'));
			$amt = floatval($row['total_pending'] ?? 0);
			$pts = floatval($row['total_points'] ?? 0);
			$cnt = intval($row['orders_count'] ?? 0);

			$pendingSubsByCurrency[$curr] = [
				'currency'     => $curr,
				'amount'       => $amt,
				'points'       => $pts,
				'orders_count' => $cnt,
			];
			$totalPendingSubsOrders += $cnt;
		}

		// 3. Reseller Purchases from Admin (Outflow where user_id = resellerId)
		$buyerParams = array_merge([$resellerId], $orderParams);
		$sqlPurchases = "SELECT
				o.currency,
				COUNT(o.id) AS orders_count,
				COALESCE(SUM(o.price_total), 0) AS total_spent,
				COALESCE(SUM(o.points_requested), 0) AS total_points_bought
			FROM `billing_orders` o
			WHERE o.user_id = ? AND o.status = 'approved'" . $orderDateClause . "
			GROUP BY o.currency";
		$db->query($sqlPurchases, ...$buyerParams);
		$purchaseRows = $db->get_rows() ?: [];

		$purchasesByCurrency = [];
		$totalPointsBought = 0.0;
		$totalSpentFiat = 0.0;
		foreach ($purchaseRows as $row) {
			$curr = strtoupper((string) ($row['currency'] ?? 'USD'));
			$spent = floatval($row['total_spent'] ?? 0);
			$pts = floatval($row['total_points_bought'] ?? 0);

			$purchasesByCurrency[$curr] = [
				'currency'     => $curr,
				'spent'        => $spent,
				'points'       => $pts,
				'orders_count' => intval($row['orders_count'] ?? 0),
			];
			$totalPointsBought += $pts;
			$totalSpentFiat += $spent;
		}

		// Average cost per point bought
		$avgCostPerPoint = $totalPointsBought > 0 ? ($totalSpentFiat / $totalPointsBought) : 1.0;

		// 4. Calculate Net Profit on sales to sub-resellers
		// Profit = Total Revenue from Subs - (Points Sold * Avg Cost Per Point)
		$grossSalesFiat = 0.0;
		foreach ($salesByCurrency as $s) {
			$grossSalesFiat += $s['revenue'];
		}
		$costOfPointsSold = $totalPointsSoldToSubs * $avgCostPerPoint;
		$estimatedProfit = max(0.0, $grossSalesFiat - $costOfPointsSold);

		// 5. Direct Consumption by Reseller (Line / Mag creations)
		$usageDateClause = '';
		$usageParams = [$resellerId];
		if ($startDate !== null && $startDate > 0) {
			$usageDateClause .= ' AND date >= ?';
			$usageParams[] = $startDate;
		}
		if ($endDate !== null && $endDate > 0) {
			$usageDateClause .= ' AND date <= ?';
			$usageParams[] = $endDate;
		}
		$sqlResellerUsage = "SELECT
				COUNT(id) AS actions_count,
				COALESCE(SUM(cost), 0) AS total_points_consumed
			FROM `users_logs`
			WHERE owner = ? AND cost > 0" . $usageDateClause;
		$db->query($sqlResellerUsage, ...$usageParams);
		$resellerUsageRow = $db->get_row();
		$directPointsConsumed = floatval($resellerUsageRow['total_points_consumed'] ?? 0);
		$directActionsCount = intval($resellerUsageRow['actions_count'] ?? 0);

		// 6. Sub-Reseller Wallet Liabilities (Total unspent points held by downstream sub-resellers)
		$db->query("SELECT COALESCE(SUM(credits), 0) AS sub_credits, COUNT(id) AS sub_count FROM `users` WHERE owner_id = ?", $resellerId);
		$subWalletRow = $db->get_row();
		$subResellerLiability = floatval($subWalletRow['sub_credits'] ?? 0);
		$subResellersCount = intval($subWalletRow['sub_count'] ?? 0);

		// Current Reseller Balance
		$db->query("SELECT credits FROM `users` WHERE id = ?", $resellerId);
		$myWallet = $db->get_row();
		$currentBalance = floatval($myWallet['credits'] ?? 0);

		return [
			'scope'                    => 'reseller',
			'sales_by_currency'        => $salesByCurrency,
			'pending_subs_by_currency' => $pendingSubsByCurrency,
			'purchases_by_currency'    => $purchasesByCurrency,
			'total_points_sold'        => $totalPointsSoldToSubs,
			'total_points_bought'      => $totalPointsBought,
			'estimated_profit'         => $estimatedProfit,
			'avg_cost_per_point'       => round($avgCostPerPoint, 4),
			'direct_points_consumed'   => $directPointsConsumed,
			'direct_actions_count'     => $directActionsCount,
			'sub_reseller_liability'   => $subResellerLiability,
			'sub_resellers_count'      => $subResellersCount,
			'current_balance'          => $currentBalance,
			'approved_sub_orders'      => $totalSubOrdersApproved,
			'pending_sub_orders'       => $totalPendingSubsOrders,
		];
	}

	/**
	 * Get Timeseries daily chart data for revenue, points minted/sold, and points consumed.
	 *
	 * @param int|null $resellerId
	 * @param int      $days
	 * @param string|null $currency
	 * @return array<string, mixed>
	 */
	public static function getTimeseriesData(?int $resellerId = null, int $days = 30, ?string $currency = null): array {
		$db = self::db();
		$days = max(7, min($days, 90));
		$startTime = strtotime("-{$days} days 00:00:00");

		// Generate timeline map
		$datesMap = [];
		for ($i = $days; $i >= 0; $i--) {
			$d = date('Y-m-d', strtotime("-{$i} days"));
			$datesMap[$d] = [
				'date'            => $d,
				'revenue'         => 0.0,
				'points_issued'   => 0.0,
				'points_consumed' => 0.0,
			];
		}

		// 1. Orders revenue & points
		$whereOrders = "WHERE o.status = 'approved' AND o.created_at >= ?";
		$paramsOrders = [$startTime];
		if ($resellerId !== null && $resellerId > 0) {
			$whereOrders .= " AND o.seller_id = ?";
			$paramsOrders[] = $resellerId;
		}
		if ($currency !== null && $currency !== '' && $currency !== 'all') {
			$whereOrders .= " AND o.currency = ?";
			$paramsOrders[] = strtoupper($currency);
		}

		$sqlOrders = "SELECT
				DATE(FROM_UNIXTIME(o.created_at)) AS day_date,
				COALESCE(SUM(o.price_total), 0) AS daily_revenue,
				COALESCE(SUM(o.points_requested), 0) AS daily_points
			FROM `billing_orders` o
			{$whereOrders}
			GROUP BY day_date";
		$db->query($sqlOrders, ...$paramsOrders);
		$orderDays = $db->get_rows() ?: [];
		foreach ($orderDays as $r) {
			$d = (string) $r['day_date'];
			if (isset($datesMap[$d])) {
				$datesMap[$d]['revenue'] = round(floatval($r['daily_revenue'] ?? 0), 2);
				$datesMap[$d]['points_issued'] = round(floatval($r['daily_points'] ?? 0), 2);
			}
		}

		// 2. Points consumed from users_logs
		$whereLogs = "WHERE date >= ?";
		$paramsLogs = [$startTime];
		if ($resellerId !== null && $resellerId > 0) {
			$whereLogs .= " AND owner = ?";
			$paramsLogs[] = $resellerId;
		}
		$sqlLogs = "SELECT
				DATE(FROM_UNIXTIME(date)) AS day_date,
				COALESCE(SUM(cost), 0) AS daily_consumed
			FROM `users_logs`
			{$whereLogs}
			GROUP BY day_date";
		$db->query($sqlLogs, ...$paramsLogs);
		$logDays = $db->get_rows() ?: [];
		foreach ($logDays as $r) {
			$d = (string) $r['day_date'];
			if (isset($datesMap[$d])) {
				$datesMap[$d]['points_consumed'] = round(floatval($r['daily_consumed'] ?? 0), 2);
			}
		}

		$categories = [];
		$seriesRevenue = [];
		$seriesPointsIssued = [];
		$seriesPointsConsumed = [];

		foreach ($datesMap as $d => $data) {
			$categories[] = date('d M', strtotime($d));
			$seriesRevenue[] = $data['revenue'];
			$seriesPointsIssued[] = $data['points_issued'];
			$seriesPointsConsumed[] = $data['points_consumed'];
		}

		return [
			'categories'              => $categories,
			'series_revenue'          => $seriesRevenue,
			'series_points_issued'    => $seriesPointsIssued,
			'series_points_consumed'  => $seriesPointsConsumed,
		];
	}

	/**
	 * Get payment method breakdown for visual charts.
	 *
	 * @param int|null $resellerId
	 * @param int|null $startDate
	 * @param int|null $endDate
	 * @return array<int, array<string, mixed>>
	 */
	public static function getGatewaysBreakdown(?int $resellerId = null, ?int $startDate = null, ?int $endDate = null): array {
		$db = self::db();
		$where = "WHERE o.status = 'approved'";
		$params = [];

		if ($resellerId !== null && $resellerId > 0) {
			$where .= " AND o.seller_id = ?";
			$params[] = $resellerId;
		}
		if ($startDate !== null && $startDate > 0) {
			$where .= " AND o.created_at >= ?";
			$params[] = $startDate;
		}
		if ($endDate !== null && $endDate > 0) {
			$where .= " AND o.created_at <= ?";
			$params[] = $endDate;
		}

		$sql = "SELECT
				o.payment_method_id,
				o.method_name,
				o.currency,
				COUNT(o.id) AS orders_count,
				COALESCE(SUM(o.price_total), 0) AS total_amount,
				COALESCE(SUM(o.points_requested), 0) AS total_points
			FROM `billing_orders` o
			{$where}
			GROUP BY o.payment_method_id, o.method_name, o.currency
			ORDER BY total_amount DESC";
		$db->query($sql, ...$params);
		return $db->get_rows() ?: [];
	}

	/**
	 * Get top consumed packages by points spent.
	 *
	 * @param int|null $resellerId
	 * @param int|null $startDate
	 * @param int|null $endDate
	 * @param int      $limit
	 * @return array<int, array<string, mixed>>
	 */
	public static function getTopPackagesBreakdown(?int $resellerId = null, ?int $startDate = null, ?int $endDate = null, int $limit = 6): array {
		$db = self::db();
		$where = "WHERE l.cost > 0 AND l.package_id IS NOT NULL";
		$params = [];

		if ($resellerId !== null && $resellerId > 0) {
			$where .= " AND l.owner = ?";
			$params[] = $resellerId;
		}
		if ($startDate !== null && $startDate > 0) {
			$where .= " AND l.date >= ?";
			$params[] = $startDate;
		}
		if ($endDate !== null && $endDate > 0) {
			$where .= " AND l.date <= ?";
			$params[] = $endDate;
		}

		$limit = max(1, min($limit, 20));
		$sql = "SELECT
				l.package_id,
				COALESCE(p.package_name, CONCAT('Package #', l.package_id)) AS package_name,
				COUNT(l.id) AS activations_count,
				COALESCE(SUM(l.cost), 0) AS total_credits_spent
			FROM `users_logs` l
			LEFT JOIN `users_packages` p ON p.id = l.package_id
			{$where}
			GROUP BY l.package_id, p.package_name
			ORDER BY total_credits_spent DESC
			LIMIT {$limit}";
		$db->query($sql, ...$params);
		return $db->get_rows() ?: [];
	}

	/**
	 * Get Financial Ledger list of users/resellers.
	 *
	 * @param array<string, mixed> $filters
	 * @param int                  $limit
	 * @param int                  $offset
	 * @return array<int, array<string, mixed>>
	 */
	public static function getFinanceLedger(array $filters = [], int $limit = 50, int $offset = 0): array {
		$db = self::db();
		$where = "WHERE 1=1";
		$params = [];

		// Scope filtering: if scoped to a reseller, only show sub-resellers under them
		if (!empty($filters['owner_id'])) {
			$where .= " AND u.owner_id = ?";
			$params[] = intval($filters['owner_id']);
		}

		if (!empty($filters['search'])) {
			$searchTerm = '%' . trim((string) $filters['search']) . '%';
			$where .= " AND (u.username LIKE ? OR u.email LIKE ? OR u.id = ?)";
			$idVal = is_numeric($filters['search']) ? intval($filters['search']) : 0;
			$params[] = $searchTerm;
			$params[] = $searchTerm;
			$params[] = $idVal;
		}

		if (!empty($filters['group_type'])) {
			if ($filters['group_type'] === 'resellers') {
				$where .= " AND g.is_reseller = 1";
			} elseif ($filters['group_type'] === 'admins') {
				$where .= " AND g.is_admin = 1";
			}
		}

		$limit = max(1, min($limit, 200));
		$offset = max(0, $offset);

		$sql = "SELECT
				u.id,
				u.username,
				u.email,
				u.credits,
				u.status,
				u.owner_id,
				u.date_registered,
				g.group_name,
				g.is_reseller,
				g.is_admin,
				owner.username AS owner_username,
				(SELECT COUNT(*) FROM `users` sub WHERE sub.owner_id = u.id) AS sub_resellers_count,
				(SELECT COALESCE(SUM(o.price_total), 0) FROM `billing_orders` o WHERE o.user_id = u.id AND o.status = 'approved') AS total_cash_paid,
				(SELECT COALESCE(SUM(o.points_requested), 0) FROM `billing_orders` o WHERE o.user_id = u.id AND o.status = 'approved') AS total_points_bought,
				(SELECT COALESCE(SUM(l.cost), 0) FROM `users_logs` l WHERE l.owner = u.id AND l.cost > 0) AS total_points_spent,
				(SELECT COALESCE(SUM(so.price_total), 0) FROM `billing_orders` so WHERE so.seller_id = u.id AND so.status = 'approved') AS total_sales_volume,
				(SELECT COALESCE(SUM(so2.points_requested), 0) FROM `billing_orders` so2 WHERE so2.seller_id = u.id AND so2.status = 'approved') AS total_points_sold
			FROM `users` u
			INNER JOIN `users_groups` g ON g.group_id = u.member_group_id
			LEFT JOIN `users` owner ON owner.id = u.owner_id
			{$where}
			ORDER BY total_cash_paid DESC, u.credits DESC, u.id ASC
			LIMIT {$limit} OFFSET {$offset}";

		$db->query($sql, ...$params);
		$rows = $db->get_rows() ?: [];

		// Post-process rows for calculated profit and presentation
		foreach ($rows as &$r) {
			$r['credits'] = floatval($r['credits'] ?? 0);
			$r['total_cash_paid'] = floatval($r['total_cash_paid'] ?? 0);
			$r['total_points_bought'] = floatval($r['total_points_bought'] ?? 0);
			$r['total_points_spent'] = floatval($r['total_points_spent'] ?? 0);
			$r['total_sales_volume'] = floatval($r['total_sales_volume'] ?? 0);
			$r['total_points_sold'] = floatval($r['total_points_sold'] ?? 0);

			// Profit estimation for resellers:
			// Cost per point bought = total_cash_paid / total_points_bought (default 1.0)
			$costPerPoint = $r['total_points_bought'] > 0 ? ($r['total_cash_paid'] / $r['total_points_bought']) : 1.0;
			$costOfSold = $r['total_points_sold'] * $costPerPoint;
			$r['estimated_profit'] = max(0.0, $r['total_sales_volume'] - $costOfSold);
		}
		unset($r);

		return $rows;
	}

	/**
	 * Count total rows matching ledger filters for pagination.
	 *
	 * @param array<string, mixed> $filters
	 * @return int
	 */
	public static function countFinanceLedger(array $filters = []): int {
		$db = self::db();
		$where = "WHERE 1=1";
		$params = [];

		if (!empty($filters['owner_id'])) {
			$where .= " AND u.owner_id = ?";
			$params[] = intval($filters['owner_id']);
		}

		if (!empty($filters['search'])) {
			$searchTerm = '%' . trim((string) $filters['search']) . '%';
			$where .= " AND (u.username LIKE ? OR u.email LIKE ? OR u.id = ?)";
			$idVal = is_numeric($filters['search']) ? intval($filters['search']) : 0;
			$params[] = $searchTerm;
			$params[] = $searchTerm;
			$params[] = $idVal;
		}

		if (!empty($filters['group_type'])) {
			if ($filters['group_type'] === 'resellers') {
				$where .= " AND g.is_reseller = 1";
			} elseif ($filters['group_type'] === 'admins') {
				$where .= " AND g.is_admin = 1";
			}
		}

		$sql = "SELECT COUNT(u.id) AS cnt
			FROM `users` u
			INNER JOIN `users_groups` g ON g.group_id = u.member_group_id
			{$where}";
		$db->query($sql, ...$params);
		$row = $db->get_row();
		return intval($row['cnt'] ?? 0);
	}

	/**
	 * Get itemized statement of account for a specific user/reseller.
	 *
	 * @param int      $targetUserId
	 * @param int|null $scopeResellerId Enforce permission: null = Admin (can view anyone), int = Reseller (can view self or sub)
	 * @return array<string, mixed>|null
	 */
	public static function getUserDetailedStatement(int $targetUserId, ?int $scopeResellerId = null): ?array {
		$db = self::db();

		// Fetch user details
		$db->query(
			"SELECT
				u.id,
				u.username,
				u.email,
				u.credits,
				u.owner_id,
				u.date_registered,
				g.group_name,
				g.is_reseller,
				g.is_admin,
				owner.username AS owner_username
			FROM `users` u
			INNER JOIN `users_groups` g ON g.group_id = u.member_group_id
			LEFT JOIN `users` owner ON owner.id = u.owner_id
			WHERE u.id = ?",
			$targetUserId
		);
		$user = $db->get_row();
		if (!$user) {
			return null;
		}

		// Multi-tenant security check
		if ($scopeResellerId !== null && $scopeResellerId > 0) {
			if ($targetUserId !== $scopeResellerId && intval($user['owner_id']) !== $scopeResellerId) {
				return null; // Unauthorized access
			}
		}

		// 1. Approved Orders (Inflow: Real money paid to buy points)
		$db->query(
			"SELECT
				o.id,
				o.order_code,
				o.created_at AS timestamp,
				'deposit' AS entry_type,
				CONCAT('Points Purchase (', o.method_name, ')') AS title,
				o.points_requested AS points_in,
				0.0 AS points_out,
				o.price_total AS fiat_amount,
				o.currency,
				o.transaction_reference AS ref_code,
				'Approved' AS status
			FROM `billing_orders` o
			WHERE o.user_id = ? AND o.status = 'approved'
			ORDER BY o.created_at DESC",
			$targetUserId
		);
		$orderEntries = $db->get_rows() ?: [];

		// 2. Direct Credit Adjustments (from users_credits_logs)
		$db->query(
			"SELECT
				c.id,
				CONCAT('ADJ-', c.id) AS order_code,
				c.date AS timestamp,
				'adjustment' AS entry_type,
				COALESCE(NULLIF(c.reason, ''), 'Balance Adjustment') AS title,
				CASE WHEN c.amount >= 0 THEN c.amount ELSE 0.0 END AS points_in,
				CASE WHEN c.amount < 0 THEN ABS(c.amount) ELSE 0.0 END AS points_out,
				0.0 AS fiat_amount,
				'PTS' AS currency,
				CONCAT('Admin #', c.admin_id) AS ref_code,
				'Applied' AS status
			FROM `users_credits_logs` c
			WHERE c.target_id = ?
			ORDER BY c.date DESC",
			$targetUserId
		);
		$creditEntries = $db->get_rows() ?: [];

		// 3. Service Consumption (from users_logs: line, mag, renewal, package)
		$db->query(
			"SELECT
				l.id,
				CONCAT('SVC-', l.id) AS order_code,
				l.date AS timestamp,
				'consumption' AS entry_type,
				CONCAT(UCASE(LEFT(l.type, 1)), LCASE(SUBSTRING(l.type, 2)), ' ', l.action, COALESCE(CONCAT(' (', p.package_name, ')'), '')) AS title,
				0.0 AS points_in,
				l.cost AS points_out,
				0.0 AS fiat_amount,
				'PTS' AS currency,
				CONCAT('Ref #', l.log_id) AS ref_code,
				'Executed' AS status
			FROM `users_logs` l
			LEFT JOIN `users_packages` p ON p.id = l.package_id
			WHERE l.owner = ? AND l.cost > 0
			ORDER BY l.date DESC
			LIMIT 150",
			$targetUserId
		);
		$usageEntries = $db->get_rows() ?: [];

		// Combine and sort entries chronologically descending
		$allEntries = array_merge($orderEntries, $creditEntries, $usageEntries);
		usort($allEntries, static function ($a, $b) {
			return ($b['timestamp'] ?? 0) <=> ($a['timestamp'] ?? 0);
		});

		// Calculate Lifetime Totals
		$totalCashPaid = 0.0;
		$totalPointsIn = 0.0;
		$totalPointsOut = 0.0;
		foreach ($allEntries as $e) {
			$totalPointsIn += floatval($e['points_in'] ?? 0);
			$totalPointsOut += floatval($e['points_out'] ?? 0);
			if ($e['entry_type'] === 'deposit') {
				$totalCashPaid += floatval($e['fiat_amount'] ?? 0);
			}
		}

		return [
			'user'              => $user,
			'total_cash_paid'   => $totalCashPaid,
			'total_points_in'   => $totalPointsIn,
			'total_points_out'  => $totalPointsOut,
			'current_balance'   => floatval($user['credits'] ?? 0),
			'entries'           => array_slice($allEntries, 0, 200),
		];
	}

	/**
	 * Export financial ledger data as a downloadable CSV.
	 *
	 * @param array<string, mixed> $filters
	 * @return string
	 */
	public static function exportLedgerCsv(array $filters = []): string {
		$rows = self::getFinanceLedger($filters, 1000, 0);

		$output = fopen('php://temp', 'r+');
		// UTF-8 BOM for Excel Arabic / International display
		fputs($output, "\xEF\xBB\xBF");

		// Header
		fputcsv($output, [
			'User ID',
			'Username',
			'Email',
			'Role / Group',
			'Parent Owner',
			'Current Credits',
			'Total Cash Deposited',
			'Total Points Purchased',
			'Total Points Consumed',
			'Sub-Resellers Count',
			'Sub-Reseller Sales Volume',
			'Estimated Profit',
			'Registration Date',
		]);

		foreach ($rows as $r) {
			fputcsv($output, [
				$r['id'],
				$r['username'],
				$r['email'],
				$r['group_name'],
				$r['owner_username'] ?: 'Direct (Admin)',
				number_format($r['credits'], 2, '.', ''),
				number_format($r['total_cash_paid'], 2, '.', ''),
				number_format($r['total_points_bought'], 2, '.', ''),
				number_format($r['total_points_spent'], 2, '.', ''),
				$r['sub_resellers_count'],
				number_format($r['total_sales_volume'], 2, '.', ''),
				number_format($r['estimated_profit'], 2, '.', ''),
				$r['date_registered'] ? date('Y-m-d H:i', (int) $r['date_registered']) : 'N/A',
			]);
		}

		rewind($output);
		$csv = stream_get_contents($output);
		fclose($output);

		return (string) $csv;
	}
}
