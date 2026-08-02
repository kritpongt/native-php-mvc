<?php
declare(strict_types=1);

use App\Controllers\Backoffice\AuthController;
use App\Controllers\Backoffice\DashboardController;
use App\Middlewares\Auth;
use App\Middlewares\Guest;
use Core\Router;

return static function(Router $router): void{

	// guest-only zone
	$router->group('/backoffice', [Guest::class], static function(Router $r): void{
		$r->get('/login', [AuthController::class, 'create']);
		$r->post('/login', [AuthController::class, 'store']);
	});

	// authenticated zone - every route in this block is protected by construction
	$router->group('/backoffice', [Auth::class], static function(Router $r): void{
		$r->get('', [DashboardController::class, 'index']);
		$r->post('/logout', [AuthController::class, 'destroy']);
	});
};