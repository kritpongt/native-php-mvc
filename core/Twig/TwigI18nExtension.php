<?php
declare(strict_types=1);

namespace Core\Twig;

use Core\Translator;
use Core\Url;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class TwigI18nExtension extends AbstractExtension
{
	public function __construct(
		private readonly Translator $translator,
		private readonly Url $url,
	){}

	/** @return list<TwigFunction> */
	public function getFunctions(): array
	{
		// first-class callable syntax (...) = pass the method itself as the handler.
		// NONE declare is_safe -> Twig escapes the result, so :placeholder values
		// (which may hold user data) stay escaped. Matches the autoescape-always rule.
		return [
			new TwigFunction('t', $this->translator->get(...)),
			new TwigFunction('url', $this->url->to(...)),
			new TwigFunction('to_locale', $this->url->toLocale(...)),
		];
	}
}