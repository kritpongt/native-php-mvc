<?php
declare(strict_types=1);

namespace App\Middlewares;

use Closure;
use Core\Middleware;
use Core\Request;
use Core\Response;

final class SecurityHeaders implements Middleware
{
	public function handle(Request $request, Closure $next): Response
	{
		// run everything first, stamp headers on the way OUT -
		// this way even 404/403 response carry them
		$response = $next($request);

		$csp = implode('; ', [
			"default-src 'self'",
			"img-src 'self' data:",
		]);

		return $response
			// only my own domain may serve scripts/css/img - THE reason htmx
			// is self-hosted in public/assets/ instead of a CDN
			->withHeader('Content-Security-Policy', $csp)
			// browser must not guess ("sniff") content types
			->withHeader('X-Content-Type-Options', 'nosniff')
			// no <iframe> embedding - kills clickjacking
			->withHeader('X-Frame-Options', 'DENY')
			// referrer stays inside my own site
			->withHeader('Referrer-Policy', 'same-origin');
	}
}