<?php
declare(strict_types=1);

namespace Core;

final class MiddlewareDef
{
	/** @var list<string> */
	public readonly array $params;

	/** @param class-string<ParameterizedMiddleware> $class */
	public function __construct(
		public readonly string $class,
		string ...$params
	){
		$this->params = array_values($params);
	}
}