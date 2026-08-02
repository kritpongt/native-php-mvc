<?php
declare(strict_types=1);

namespace App\Middlewares;

use Closure;
use Core\Csrf;
use Core\Middleware;
use Core\Request;
use Core\Response;

final class VerifyCsrf implements Middleware
{
	public function __construct(private readonly Csrf $csrf){}

	public function handle(Request $request, Closure $next): Response
	{
		$mutating = in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true);

		if($mutating){
			// classic form sends hidden field, HTMX sends header (hx-headers meta)
			$token = $request->input('_csrf') ?? $request->header('x-csrf-token');

			if(!is_string($token) || !$this->csrf->verify($token)){
				// short-circuit: controller never runs
				return Response::html('<h1>403 Forbidden - CSRF token mismatch</h1>', 403);
			}
		}

		return $next($request);
	}
}