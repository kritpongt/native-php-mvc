<?php
declare(strict_types=1);

namespace Core;

final class Url
{
	public function __construct(private readonly Translator $translator){}

	/** to('/backoffice') -> "/en/backoffice" (prefix = active locale) */
	public function to(string $path): string
	{
		return $this->build($this->translator->locale(), $path);
	}

	/** the language switcher uses this */
	public function toLocale(string $locale, string $path): string
	{
		return $this->build($locale, $path);
	}

	private function build(string $locale, string $path): string
	{
		// normalize: exactly one leading slash, drop it entirely for root
		$path = '/'.ltrim($path, '/');
		if($path === '/'){ $path = ''; }

		return '/'.$locale.$path;
	}
}