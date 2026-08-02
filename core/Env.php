<?php
declare(strict_types=1);

namespace Core;

use RuntimeException;

final class Env
{
	/**
	 * Parse .env file into $_ENV. Call ONCE at boot.
	 */
	public static function load(string $path): void
	{
		if(!is_readable($path)){ throw new RuntimeException("Cannot read env file: {$path}"); }

		$lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		foreach($lines as $line){
			$line = trim($line);

			if($line === '' || str_starts_with($line, '#')){ continue; }

			$pos = strpos($line, '=');
			if($pos === false){ continue; }

			$key = trim(substr($line, 0, $pos));
			$value = self::stripQuotes(trim(substr($line, $pos + 1)));

			if(getenv($key) === false && !array_key_exists($key, $_ENV)){
				$_ENV[$key] = $value;
			}
		}
	}

	/**
	 * Read one value. ALLOWED ONLY INSIDE config/ files (project rule).
	 */
	public static function get(string $key, ?string $default = null): ?string
	{
		$value = $_ENV[$key] ?? getenv($key);
		return ($value === false || $value === null) ? $default : (string) $value;
	}

	private static function stripQuotes(string $value): string
	{
		if(strlen($value) >= 2){
			$first = $value[0];
			$last = $value[strlen($value) - 1];

			if(($first === '"' && $last === '"') || ($first === "'" && $last === "'")){
				return substr($value, 1, -1);
			}
		}

		return $value;
	}
}