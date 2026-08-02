<?php
declare(strict_types=1);

namespace Core;

final class Config
{
	/** @var array<string, mixed> */
	private array $items = [];

	public function __construct(string $configDir)
	{
		// every config/*.php returns an array: filename becomes the key
		// config/app.php -> $items['app']
		foreach(glob($configDir.'/*.php') ?: [] as $file){
			$this->items[basename($file, '.php')] = require $file;
		}
	}

	/**
	 * Dot notation: get('database.driver') walks items['database']['driver']
	 */
	public function get(string $key, mixed $default = null): mixed
	{
		$value = $this->items;
		foreach(explode('.', $key) as $segment){
			if(!is_array($value) || !array_key_exists($segment, $value)){
				return $default;
			}
			$value = $value[$segment];
		}

		return $value;
	}
}