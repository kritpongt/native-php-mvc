<?php
declare(strict_types=1);

use App\Controllers\HomeController;
use Core\Router;

return static function(Router $router): void{
	$router->get('/', [HomeController::class, 'index']);
	$router->post('/greet', [HomeController::class, 'greet']);
};