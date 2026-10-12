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

use XcVm\Core\Container\ServiceContainer;
use XcVm\Core\Http\Router;
use XcVm\Core\Module\BaseModule;
use XcVm\Core\Module\Contract\NavbarProviderInterface;
use XcVm\Core\Module\Contract\ResellerNavbarProviderInterface;
use XcVm\Core\Module\Contract\RouteProviderInterface;
use XcVm\Core\Module\NavbarItem;
use XcVm\Core\Module\NavbarRegistry;
use XcVm\Core\Module\ResellerNavbarRegistry;

/**
 * BillingModule — main module definition for the Billing and Points system.
 *
 * Provides:
 * - Admin, Reseller, and Sub-Reseller navbar registration
 * - Route and API registration
 * - Point purchase order lifecycle and payment method management
 *
 * @package XC_VM_Module_Billing
 */
class BillingModule extends BaseModule implements RouteProviderInterface, NavbarProviderInterface, ResellerNavbarProviderInterface {

	public function getName(): string {
		return 'billing';
	}

	public function getVersion(): string {
		return '1.0.1';
	}

	public function boot(ServiceContainer $container): void {
		// Module container bindings if needed
	}

	/**
	 * Self-setup hook executed on module installation.
	 */
	public function install(): void {
		$uploadDir = BillingService::UPLOAD_DIR;
		if (!is_dir($uploadDir)) {
			@mkdir($uploadDir, 0775, true);
		}
		@chmod($uploadDir, 0775);

		// Install execution guards in upload directory
		$htaccess = $uploadDir . '.htaccess';
		if (!file_exists($htaccess)) {
			$htContent = "# Disable script execution\n<FilesMatch \"\\.(php|php5|php7|php8|phtml|phar|pl|py|cgi|sh|bash)$\">\n    Order Deny,Allow\n    Deny from all\n</FilesMatch>\nOptions -Indexes -ExecCGI\nphp_flag engine off\n";
			@file_put_contents($htaccess, $htContent);
		}

		$indexHtml = $uploadDir . 'index.html';
		if (!file_exists($indexHtml)) {
			@file_put_contents($indexHtml, "<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body><h1>Directory access is forbidden.</h1></body></html>\n");
		}
	}

	/**
	 * Register HTTP routes and API endpoints.
	 */
	public function registerRoutes(Router $router): void {
		// Group 'billing'
		$router->group('billing', function (Router $r) {
			// Main dashboard (context-aware: admin vs reseller)
			$r->any('', [self::class, 'routeIndex']);
			$r->any('overview', [BillingController::class, 'adminOverview']);
			$r->any('resellers', [BillingController::class, 'adminResellers']);
			$r->any('methods', [self::class, 'routeMethods']);
			$r->any('orders', [self::class, 'routeOrders']);
			$r->any('buy', [BillingController::class, 'subresellerBuy']);
			$r->any('my_orders', [BillingController::class, 'subresellerOrders']);
			$r->any('receipt', [BillingController::class, 'viewReceipt']);
		});

		// API routes
		$router->api('billing_get_methods', [BillingController::class, 'apiGetMethods']);
		$router->api('billing_save_method', [BillingController::class, 'apiSaveMethod']);
		$router->api('billing_delete_method', [BillingController::class, 'apiDeleteMethod']);
		$router->api('billing_toggle_method', [BillingController::class, 'apiToggleMethod']);
		$router->api('billing_submit_order', [BillingController::class, 'apiSubmitOrder']);
		$router->api('billing_get_order', [BillingController::class, 'apiGetOrder']);
		$router->api('billing_approve_order', [BillingController::class, 'apiApproveOrder']);
		$router->api('billing_reject_order', [BillingController::class, 'apiRejectOrder']);
		$router->api('billing_drilldown', [BillingController::class, 'apiDrilldown']);
	}

	/**
	 * Context-aware entry for /billing: branches to Admin vs Reseller.
	 */
	public static function routeIndex(): void {
		$controller = new BillingController();
		$scope = $GLOBALS['scope'] ?? 'admin';
		if ($scope === 'reseller') {
			$controller->resellerIndex();
		} else {
			$controller->adminOverview();
		}
	}

	/**
	 * Context-aware entry for /billing/methods.
	 */
	public static function routeMethods(): void {
		$controller = new BillingController();
		$scope = $GLOBALS['scope'] ?? 'admin';
		if ($scope === 'reseller') {
			$controller->resellerMethods();
		} else {
			$controller->adminMethods();
		}
	}

	/**
	 * Context-aware entry for /billing/orders.
	 */
	public static function routeOrders(): void {
		$controller = new BillingController();
		$scope = $GLOBALS['scope'] ?? 'admin';
		if ($scope === 'reseller') {
			$controller->resellerOrders();
		} else {
			$controller->adminOrders();
		}
	}

	/**
	 * Register items in the Admin Navbar.
	 */
	public function registerNavbar(NavbarRegistry $registry): void {
		$registry->add((new NavbarItem('billing'))
			->url('#')
			->label('', 'Billing & Points')
			->icon('ti tabler-credit-card')
			->order(350));

		$registry->add((new NavbarItem('billing.overview'))
			->parent('billing')
			->url('billing')
			->label('', 'Overview & Stats')
			->order(10));

		$registry->add((new NavbarItem('billing.resellers'))
			->parent('billing')
			->url('billing_resellers')
			->label('', 'Resellers Directory')
			->order(20));

		$registry->add((new NavbarItem('billing.orders'))
			->parent('billing')
			->url('billing_orders')
			->label('', 'All Orders')
			->order(30));

		$registry->add((new NavbarItem('billing.admin_methods'))
			->parent('billing')
			->url('billing_methods?type=admin')
			->label('', 'Admin Methods')
			->order(40));

		$registry->add((new NavbarItem('billing.reseller_methods'))
			->parent('billing')
			->url('billing_methods?type=reseller')
			->label('', 'Reseller Methods')
			->order(45));
	}

	/**
	 * Register items in the Reseller / Sub-Reseller Navbar.
	 */
	public function registerResellerNavbar(ResellerNavbarRegistry $registry): void {
		$registry->add((new NavbarItem('billing'))
			->url('#')
			->label('', 'Payments & Billing')
			->icon('ti tabler-credit-card')
			->order(250));

		$registry->add((new NavbarItem('billing.overview'))
			->parent('billing')
			->url('billing')
			->label('', 'Overview & Wallet')
			->order(10));

		$registry->add((new NavbarItem('billing.buy'))
			->parent('billing')
			->url('billing_buy')
			->label('', 'Buy Points')
			->order(20));

		$registry->add((new NavbarItem('billing.methods'))
			->parent('billing')
			->url('billing_methods')
			->label('', 'My Payment Methods')
			->permissions(['create_sub_resellers'])
			->order(30));

		$registry->add((new NavbarItem('billing.orders'))
			->parent('billing')
			->url('billing_orders')
			->label('', 'Sub-Reseller Orders')
			->permissions(['create_sub_resellers'])
			->order(40));

		$registry->add((new NavbarItem('billing.my_orders'))
			->parent('billing')
			->url('billing_my_orders')
			->label('', 'My Orders History')
			->order(50));
	}
}
