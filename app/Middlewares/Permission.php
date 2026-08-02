<?php
declare(strict_types=1);

namespace App\Middlewares;

use App\Services\AuthService;
use App\Services\AuthorizationService;
use Closure;
use Core\MiddlewareDef;
use Core\ParameterizedMiddleware;
use Core\Request;
use Core\Response;

final class Permission implements ParameterizedMiddleware
{
	/** @var list<string> */
	private array $required = [];

	public function __construct(
		private readonly AuthService $auth,
		private readonly AuthorizationService $authz
	){}

	/** route-file sugar: Permission::with('users.manage') - typed, greppable */
	public static function with(string ...$permissions): MiddlewareDef
	{
		return new MiddlewareDef(self::class, ...$permissions);
	}

	public function withParams(string ...$params): static
	{
		$clone = clone $this;
		$clone->required = array_values($params);

		return $clone;
	}

	public function handle(Request $request, Closure $next): Response
	{
		$user = $this->auth->user();

		// Auth must sit BEFORE Permission in the route - null here = misconfigured
		if($user === null){
			return Response::redirect('/backoffice/login');
		}

		foreach($this->required as $permission){
			if(!$this->authz->can($user, $permission)){
				return Response::html('<h1>403 Forbidden</h1>', 403);
			}
		}

		return $next($request);
	}
}