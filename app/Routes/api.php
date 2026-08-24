<?php
declare(strict_types=1);

use App\Controllers\Api\HealthController;
use Core\Router;

return static function(Router $router): void {
	$router->group('/api', [], static function(Router $r): void {
		$r->get('/healthcheck', [HealthController::class, 'index']);
	});
};