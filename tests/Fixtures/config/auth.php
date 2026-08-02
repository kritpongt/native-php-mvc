<?php
declare(strict_types=1);

// fixture values - AuthServiceTest builds a REAL Config pointed here (CLAUDE.md rule:
// Config is never mocked). Only auth.php needed; Config globs whatever dir it is given
return [
	'login_max_attempts' => 5,
	'login_window_minutes' => 15,
	'remember_lifetime_days' => 30,
	'remember_cookie' => 'remember_me',
];