<?php
declare(strict_types=1);

namespace App\Middlewares;

use App\Services\AuthService;
use Closure;
use Core\Middleware;
use Core\Request;
use Core\Response;

final class Guest implements Middleware
{
	public function __construct(private readonly AuthService $auth){}

	public function handle(Request $request, Closure $next): Response
	{
		// already logged in - login page makes no sense, go home
		if($this->auth->check()){
			return Response::redirect('/backoffice');
		}

		return $next($request);
	}
}