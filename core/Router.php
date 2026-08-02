<?php
declare(strict_types=1);

namespace Core;

use InvalidArgumentException;

final class Router
{
	/**
	 * routes[method][path] = ['handler' => [class, method], 'middleware' => [...]]
	 * 
	 * @var array<string, array<string, array{handler: array{class-string, string}, middleware: list<class-string|MiddlewareDef>}>>
	 */
	private array $routes = [];

	/**
	 * Active group() nesting. Every route declared inside inherits
	 * each entry's prefix and middleware.
	 * 
	 * @var list<array{prefix: string, middleware: list<class-string|MiddlewareDef>}>
	 */
	private array $groupStack = [];

	private readonly Pipeline $pipeline;

	// router asks the container instead of new-ing controllers itself
	public function __construct(private readonly Container $container)
	{
		$this->pipeline = new Pipeline($container);
	}

	/**
	 * Security: the whole area shares ONE Auth declaration -
	 * a new route inside cannot forget it (default-deny by construction).
	 * Nesting allowed. Closure here is declaration structure, not a handler.
	 * 
	 * @param list<class-string|MiddlewareDef> $middleware
	 * @param callable(Router): void $routes
	 */
	public function group(string $prefix, array $middleware, callable $routes): void
	{
		$this->groupStack[] = ['prefix' => $prefix, 'middleware' => $middleware];
		$routes($this);
		array_pop($this->groupStack);
	}

	/**
	 * @param array{class-string, string} $handler
	 * @param list<class-string|MiddlewareDef> $middleware
	 */
	public function get(string $path, array $handler, array $middleware = []): void
	{
		$this->add('GET', $path, $handler, $middleware);
	}

	/**
	 * @param array{class-string, string} $handler
	 * @param list<class-string|MiddlewareDef> $middleware
	 */
	public function post(string $path, array $handler, array $middleware = []): void
	{
		$this->add('POST', $path, $handler, $middleware);
	}

	/**
	 * @param array{class-string, string} $handler
	 * @param list<class-string|MiddlewareDef> $middleware
	 */
	public function put(string $path, array $handler, array $middleware = []): void
	{
		$this->add('PUT', $path, $handler, $middleware);
	}

	/**
	 * @param array{class-string, string} $handler
	 * @param list<class-string|MiddlewareDef> $middleware
	 */
	public function patch(string $path, array $handler, array $middleware = []): void
	{
		$this->add('PATCH', $path, $handler, $middleware);
	}

	/**
	 * @param array{class-string, string} $handler
	 * @param list<class-string|MiddlewareDef> $middleware
	 */
	public function delete(string $path, array $handler, array $middleware = []): void
	{
		$this->add('DELETE', $path, $handler, $middleware);
	}

	private function add(string $method, string $path, array $handler, array $middleware): void
	{
		// project rule: [Controller::class, 'method'] ONLY - closures forbidden.
		// keeps web.php a readable security audit surface.
		if(
			count($handler) !== 2
			|| !is_string($handler[0] ?? null)
			|| !is_string($handler[1] ?? null)
		){
			throw new InvalidArgumentException("Route {$method} {$path}: handler must be [Controller::class, 'method']");
		}

		// innermost group first: prepend its prefix, wrap its middleware OUTSIDE,
		// final order = [outer group..., inner group..., route own middleware]
		foreach(array_reverse($this->groupStack) as $group){
			$path = rtrim($group['prefix'], '/').$path;
			$middleware = [...$group['middleware'], ...$middleware];
		}

		$this->routes[$method][$path] = [
			'handler' => $handler,
			'middleware' => $middleware
		];
	}

	public function dispatch(Request $request): Response
	{
		foreach($this->routes[$request->method()] ?? [] as $routePath => $route){
			$params = $this->matchPath($routePath, $request->path());

			if($params === null){ continue; }

			// destructuring: $route['handler'][0] -> $class, $route['handler'][1] -> $method
			[$class, $method] = $route['handler'];

			$controller = $this->container->get($class);

			// innermost layer: the controller call itself
			$handler = static fn(Request $req): Response => $controller->{$method}($req, ...$params);

			// route pipeline
			return $this->pipeline->send($request, $route['middleware'], $handler);
		}

		return Response::notFound();
	}

	/**
	 * Compare segment by segment. '{name}' segment captures the value.
	 * Returns captured params, or null when not matched.
	 * 
	 * @return array<string, string>|null
	 */
	private function matchPath(string $routePath, string $requestPath): ?array
	{
		$routeSegments = explode('/', trim($routePath, '/'));
		$requestSegments = explode('/', trim($requestPath, '/'));
	
		if(count($routeSegments) !== count($requestSegments)){ return null; }

		$params = [];

		foreach($routeSegments as $i => $segment){
			if(str_starts_with($segment, '{') && str_ends_with($segment, '}')){
				// {id} -> params['id'] = actual value from URL
				$params[substr($segment, 1, -1)] = $requestSegments[$i];
			}else if($segment !== $requestSegments[$i]){
				return null; // fixed segment must match exactly
			}
		}

		return $params;
	}
}