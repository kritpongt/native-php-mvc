<?php
declare(strict_types=1);

use App\Controllers\Backoffice\AuthController;
use App\Controllers\Backoffice\DashboardController;
use App\Controllers\Backoffice\UserController;
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

		// users
		$r->get('/users', [UserController::class, 'index'], [Permission::with('users.view')]);
		$r->get('/users/create', [UserController::class, 'create'], [Permission::with('users.manage')]);
		$r->post('/users', [UserController::class, 'store'], [Permission::with('users.manage')]);

		$r->post('/logout', [AuthController::class, 'destroy']);
	});
};