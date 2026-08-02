<?php
declare(strict_types=1);

namespace App\Middlewares;

use Closure;
use Core\Middleware;
use Core\Request;
use Core\Response;
use Core\SessionInterface;

final class StartSession implements Middleware
{
	public function __construct(private readonly SessionInterface $session){}

	public function handle(Request $request, Closure $next): Response
	{
		// must sit BEFORE VerifyCsrf in the pipeline - csrf lives in the session
		$this->session->start();

		return $next($request);
	}
}