<?php
declare(strict_types=1);

namespace App\Middlewares;

use Closure;
use Core\Config;
use Core\Middleware;
use Core\Request;
use Core\Response;
use Core\Translator;

final class ResolveLocale implements Middleware
{
	private const ONE_YEAR = 31536000;

	public function __construct(
		private readonly Config $config,
		private readonly Translator $translator
	){}

	public function handle(Request $request, Closure $next): Response
	{
		$cookieName = (string) $this->config->get('locale.cookie', 'locale');

		$path = $request->path(); // e.g. /en/backoffice
		$segments = explode('/', ltrim($path, '/'));
		$localeSegment = $segments[0] ?? '';

		// valid /{locale} prefix -> activate it, strip it, remember it
		if($this->translator->isSupported($localeSegment)){
			$this->translator->setLocale($localeSegment);

			// remainder after the locale segment; keep one leading slash, ''-> '/'
			$rest = '/'.implode('/', array_slice($segments, 1));

			$response = $next($request->withPath($rest));

			return $response->withCookie($cookieName, $localeSegment, self::ONE_YEAR);
		}

		$cookie = (string) $request->cookie($cookieName, '');
		$locale = $this->translator->isSupported($cookie)
			? $cookie
			: (string) $this->config->get('locale.default', 'en');

		if($request->method() === 'GET'){
			return Response::redirect('/'.$locale.$path, 302);
		}

		$this->translator->setLocale($locale);

		return $next($request);
	}
}