<?php
declare(strict_types=1);

use Core\Env;

return [
	'driver' => Env::get('DB_DRIVER', 'mysql'),
	'host' => Env::get('DB_HOST', '127.0.0.1'),
	'port' => (int) Env::get('DB_PORT', '3306'),
	'name' => Env::get('DB_NAME', ''),
	'user' => Env::get('DB_USER', ''),
	'pass' => Env::get('DB_PASS', ''),
];