<?php
declare(strict_types=1);

use Core\Env;

return [
	'name' => Env::get('SESSION_NAME', 'app_session'),
	// secure cookie travels over https ONLY. dev runs `http://localhost` -> false in .env
	'secure' => filter_var(Env::get('SESSION_SECURE', 'true'), FILTER_VALIDATE_BOOL),
	// 0 = cookie dies when browser closes
	'lifetime' => (int) Env::get('SESSION_LIFETIME', '0')
];