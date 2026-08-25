<?php
declare(strict_types=1);

use App\Controllers\Backoffice\AuditLogController;
use App\Controllers\Backoffice\AuthController;
use App\Controllers\Backoffice\DashboardController;
use App\Controllers\Backoffice\RoleController;
use App\Controllers\Backoffice\UserController;
use App\Controllers\Backoffice\QuotationController;
use App\Controllers\Backoffice\InvoiceController;
use App\Middlewares\Auth;
use App\Middlewares\Guest;
use App\Middlewares\Permission;
use Core\Router;

return static function(Router $router): void{
	// guest-only zone
	$router->group('/backoffice', [Guest::class], static function(Router $r): void{
		$r->get('/login', [AuthController::class, 'create']);
		$r->post('/login', [AuthController::class, 'store']);
	});

	// authenticated zone - every route in this block is protected by construction
	$router->group('/backoffice', [Auth::class], static function(Router $r): void{
		// dashboard
		$r->get('', [DashboardController::class, 'index']);

		// quotation
		$r->get('/quotation', [QuotationController::class, 'index']);
		$r->get('/quotation/create', [QuotationController::class, 'create']);
		$r->get('/quotation/{id}', [QuotationController::class, 'show']);
		$r->post('/quotation', [QuotationController::class, 'store']);
		$r->post('/quotation/{id}/update', [QuotationController::class, 'update']);
		$r->post('/quotation/{id}/status', [QuotationController::class, 'status']);

		// invoice
		$r->get('/invoice', [InvoiceController::class, 'index']);
		$r->get('/invoice/{id}', [InvoiceController::class, 'show']);
		$r->post('/invoice/{id}/status', [InvoiceController::class, 'status']);

		// users
		$r->get('/users', [UserController::class, 'index'], [Permission::with('users.view')]);
		$r->get('/users/create', [UserController::class, 'create'], [Permission::with('users.manage')]);
		$r->get('/users/{id}/edit', [UserController::class, 'edit'], [Permission::with('users.manage')]);
		$r->post('/users', [UserController::class, 'store'], [Permission::with('users.manage')]);
		$r->post('/users/{id}/update', [UserController::class, 'update'], [Permission::with('users.manage')]);
		$r->post('/users/{id}/delete', [UserController::class, 'destroy'], [Permission::with('users.manage')]);

		// roles
		$r->get('/roles', [RoleController::class, 'index'], [Permission::with('roles.view')]);
		$r->get('/roles/create', [RoleController::class, 'create'], [Permission::with('roles.manage')]);
		$r->get('/roles/{id}/edit', [RoleController::class, 'edit'], [Permission::with('roles.manage')]);
		$r->post('/roles', [RoleController::class, 'store'], [Permission::with('roles.manage')]);
		$r->post('/roles/{id}/update', [RoleController::class, 'update'], [Permission::with('roles.manage')]);
		$r->post('/roles/{id}/delete', [RoleController::class, 'destroy'], [Permission::with('roles.manage')]);

		// audit logs
		$r->get('/audit', [AuditLogController::class, 'index']);

		$r->post('/logout', [AuthController::class, 'destroy']);
	});
};