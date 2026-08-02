<?php
declare(strict_types=1);

namespace Core;

use Closure;
use InvalidArgumentException;

final class Pipeline
{
	public function __construct(private readonly Container $container){}

	/**
	 * First middleware in the list = outermost onion layer.
	 * 
	 * @param list<class-string<Middleware>|MiddlewareDef> $middleware
	 * @param Closure(Request): Response $destination innermost handler
	 */
	public function send(Request $request, array $middleware, Closure $destination): Response
	{
		$next = $destination;

		// build inside-out: wrap the destination with the LAST middleware first,
		// so the FIRST one ends up as the outermost layer
		foreach(array_reverse($middleware) as $entry){
			$layer = $this->resolve($entry);
			// arrow fn captures CURRENT $layer and $next by value - each loop
			// freezes one onion layer around the previous one
			$next = static fn(Request $req): Response => $layer->handle($req, $next);
		}

		return $next($request);
	}

	private function resolve(string|MiddlewareDef $entry): Middleware
	{
		if($entry instanceof MiddlewareDef){
			$layer = $this->container->get($entry->class);

			if(!$layer instanceof ParameterizedMiddleware){
				throw new InvalidArgumentException("{$entry->class} takes params but does not implement ParameterizedMiddleware");
			}

			// clone with params - container singleton keeps no request state
			return $layer->withParams(...$entry->params);
		}

		return $this->container->get($entry);
	}
}