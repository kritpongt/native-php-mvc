<?php
declare(strict_types=1);

namespace Core;

use Closure;

interface Middleware
{
	/**
	 * Do work, then call $next($request) to pass deeper.
	 * NOT calling $next = short-circuit: request stops here (403, redirect).
	 */
	public function handle(Request $request, Closure $next): Response;
}