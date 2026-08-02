<?php
declare(strict_types=1);

use Core\Env;

return [
	// default locale, served at "/en/..."
	'default' => Env::get('LOCALE_DEFAULT', 'en'),
	// used when a key is missing in the active locale
	'fallback' => Env::get('LOCALE_FALLBACK', 'en'),
	// whitelist: the ONLY locales ever trusted (path-traversal guard)
	'whitelist' => ['en', 'th'],
	// cookie that remembers a manual choice
	'cookie' => 'locale',
	'path' => dirname(__DIR__).'/app/Lang',
];