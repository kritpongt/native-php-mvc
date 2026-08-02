<?php
declare(strict_types=1);

namespace App\Middlewares;

use Closure;
use Core\ErrorHandler;
use Core\Middleware;
use Core\Request;
use Core\Response;
use Throwable;

final class HandleErrors implements Middleware
{
	public function __construct(private readonly ErrorHandler $errors){}

	public function handle(Request $request, Closure $next): Response
	{
		try{
			return $next($request);
		}catch(Throwable $e){
			return $this->errors->render($e);
		}
	}
}