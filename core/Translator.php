<?php
declare(strict_types=1);

namespace Core;

final class Translator
{
	private string $locale;
	private readonly string $fallback;
	private readonly string $path;

	/** @var list<string> */
	private readonly array $whitelist;

	/** @var array<string, array<string, mixed>> "{locale}.{domain}" => data, loaded once*/
	private array $loaded = [];

	public function __construct(Config $config)
	{
		$this->whitelist = (array) $config->get('locale.whitelist', ['en']);
		$this->fallback = (string) $config->get('locale.fallback', 'en');
		$this->locale = (string) $config->get('locale.default', 'en');
		$this->path = (string) $config->get('locale.path');
	}

	/** whitelist-checked: an unknown locale is ignored, never assigned */
	public function setLocale(string $locale): void
	{
		if(in_array($locale, $this->whitelist, true)){
			$this->locale = $locale;
		}
	}

	public function locale(): string
	{
		return $this->locale;
	}

	/** @return list<string> the whitelist - the language switcher loops this */
	public function supported(): array
	{
		return $this->whitelist;
	}

	public function isSupported(string $locale): bool
	{
		return in_array($locale, $this->whitelist, true);
	}

	/**
	 * t('messages.greeting', [':name' => 'John Wick']) -> "Hello, John Wick"
	 * missing key -> fallback locale -> else the key itself (loud, visible).
	 * 
	 * @param array<string, string> $replace
	 */
	public function get(string $key, array $replace = []): string
	{
		$line = $this->line($key, $this->locale)
			?? $this->line($key, $this->fallback)
			?? $key;

		// strtr swaps all :placeholders in one pass
		return $replace === [] ? $line : strtr($line, $replace);
	}

	private function line(string $key, string $locale): ?string
	{
		[$domain, $rest] = array_pad(explode('.', $key, 2), 2, '');
		if($rest === ''){ return null; }

		$data = $this->load($locale, $domain);

		foreach(explode('.', $rest) as $segment){
			if(!is_array($data) || !array_key_exists($segment, $data)){
				return null;
			}
			$data = $data[$segment];
		}

		return is_string($data) ? $data : null;
	}

	private function load(string $locale, string $domain): array
	{
		$cacheKey = "{$locale}.{$domain}";
		if(isset($this->loaded[$cacheKey])){
			return $this->loaded[$cacheKey];
		}

		if(!$this->isSupported($locale) || preg_match('/^[a-z_]+$/', $domain) !== 1){
			return $this->loaded[$cacheKey] = [];
		}

		$file = "{$this->path}/{$locale}/{$domain}.php";

		return $this->loaded[$cacheKey] = is_file($file) ? (array) require $file : [];
	}
}