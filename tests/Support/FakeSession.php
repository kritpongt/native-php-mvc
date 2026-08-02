<?php
declare(strict_types=1);

namespace Tests\Support;

use Core\SessionInterface;

// in-memory session for unit tests - no $_SESSION, no PHP globals.
// public counters let tests assert regenerate/invalidate actually happened
final class FakeSession implements SessionInterface
{
	/** @var array<string, mixed> */
	private array $data = [];

	/** @var array<string, mixed> */
	private array $flash = [];

	public int $regenerateCount = 0;
	public int $invalidateCount = 0;

	public function start(): void {}

	public function get(string $key, mixed $default = null): mixed
	{
		return $this->data[$key] ?? $default;
	}

	public function set(string $key, mixed $value): void
	{
		$this->data[$key] = $value;
	}

	public function has(string $key): bool
	{
		return isset($this->data[$key]);
	}

	public function regenerate(): void
	{
		$this->regenerateCount++;
	}

	public function invalidate(): void
	{
		$this->invalidateCount++;
		$this->data = [];
		$this->flash = [];
	}

	public function flash(string $key, mixed $value): void
	{
		$this->flash[$key] = $value;
	}

	public function getFlash(string $key, mixed $default = null): mixed
	{
		if(!array_key_exists($key, $this->flash)){ return $default; }

		$value = $this->flash[$key];
		unset($this->flash[$key]); // read once, then gone

		return $value;
	}
}