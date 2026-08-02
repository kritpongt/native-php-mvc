<?php
declare(strict_types=1);

use Core\Env;

$root = dirname(__DIR__).'/storage';

// ## paths default to storage/ but .env can override (Docker, atomic deploys).
return [
	'twig_cache' => Env::get('TWIG_CACHE_PATH', $root.'/cache/twig'),
	'logs' => Env::get('LOG_PATH', $root.'/logs'),
	'log_retention_days' => (int) Env::get('LOG_RETENTION_DAYS', '14'),
	'log_min_level' => (string) Env::get('LOG_MIN_LEVEL', 'WARNING')
];