<?php
declare(strict_types=1);

use Core\Env;

return [
	'env' => Env::get('APP_ENV', 'production'),
	'debug' => filter_var(Env::get('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOL),
	'url' => Env::get('APP_URL', 'http://localhost:8000'),
	'timezone' => Env::get('APP_TIMEZONE', 'Asia/Bangkok'),
	'view_path' => Env::get('VIEW_PATH', dirname(__DIR__).'/app/Views'),
];