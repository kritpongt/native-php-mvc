<?php
declare(strict_types=1);

namespace Core;

use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;

final class Container
{
	/** @var array<string, callable(self): object> */
	private array $factories = [];

	/** @var array<string, bool> flag */
	private array $shared = [];

	/** @var array<string, object> built singletons + registered instances */
	private array $instances = [];

	/** @var array<string, true> guard: what we are building right now */
	private array $resolving = [];

	/** register an *already-built object* (Config from bootstrap) */
	public function instance(string $id, object $object): void
	{
		$this->instances[$id] = $object;
	}

	/** factory runs EVERY get() - fresh object each time */
	public function bind(string $id, callable $factory): void
	{
		$this->factories[$id] = $factory;
		$this->shared[$id] = false;
	}

	/** factory runs ONCE, result cached (Database = one PDO connection) */
	public function singleton(string $id, callable $factory): void
	{
		$this->factories[$id] = $factory;
		$this->shared[$id] = true;
	}

	/**
	 * @template T of object
	 * @param class-string<T> $id
	 * @return T
	 */
	public function get(string $id): object
	{
		if(isset($this->instances[$id])){
			return $this->instances[$id];
		}

		// A needs B, B needs A -> infinite loop. catch it early.
		if(isset($this->resolving[$id])){
			throw new RuntimeException("Circular dependency while resolving {$id}");
		}
		$this->resolving[$id] = true;

		try{
			$object = isset($this->factories[$id])
				? ($this->factories[$id])($this) 	// registered factory wins
				: $this->autowire($id);						// else build by reflection
		}finally{
			unset($this->resolving[$id]);
		}

		if($this->shared[$id] ?? false){
			$this->instances[$id] = $object;
		}

		return $object;
	}

	/** read constructor, resolve every parameter, build the object */
	private function autowire(string $class): object
	{
		if(!class_exists($class)){
			throw new RuntimeException("Cannot resolve {$class}: class not found");
		}

		$ref = new ReflectionClass($class);

		if(!$ref->isInstantiable()){
			// interface or abstract class - needs an explicit bind()
			throw new RuntimeException("Cannot resolve {$class}: not instantiable");
		}

		$constructor = $ref->getConstructor();

		// no constructor = zero dependencies
		if($constructor === null){ return new $class(); }

		$args = [];

		foreach($constructor->getParameters() as $param){
			$type = $param->getType();

			// class type hint -> recurse: build that dependency first
			if($type instanceof ReflectionNamedType && !$type->isBuiltin()){
				$args[] = $this->get($type->getName());
				continue;
			}

			// scalar param (string/int) - reflection can't guess a value
			if($param->isDefaultValueAvailable()){
				$args[] = $param->getDefaultValue();
				continue;
			}

			throw new RuntimeException("Cannot resolve {$class}: \${$param->getName()} has no class type and no default");
		}

		return $ref->newInstanceArgs($args);
	}
}