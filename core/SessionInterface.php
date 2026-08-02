<?php
declare(strict_types=1);

namespace Core;

interface SessionInterface
{
	/** call once per request BEFORE any session read/write (middleware's job) */
	public function start(): void;

	public function get(string $key, mixed $default = null): mixed;

	public function set(string $key, mixed $value): void;

	public function has(string $key): bool;

	/** new id, old id dies. project rule: call after EVERY login/logout */
	public function regenerate(): void;

	public function invalidate(): void;

	/** flash = value lives until read ONCE ("saved!", login error) */
	public function flash(string $key, mixed $value): void;

	public function getFlash(string $key, mixed $default = null): mixed;
}