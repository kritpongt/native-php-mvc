<?php
declare(strict_types=1);

use Core\Env;

return [
	'login_max_attempts' => (int) Env::get('AUTH_LOGIN_MAX_ATTEMPTS', '5'),
	'login_window_minutes' => (int) Env::get('AUTH_LOGIN_WINDOW_MINUTES', '15'),
	'remember_lifetime_days' => (int) Env::get('AUTH_REMEMBER_LIFETIME_DAYS', '30'),
	'remember_cookie' => Env::get('AUTH_REMEMBER_COOKIE', 'remember_me')
];