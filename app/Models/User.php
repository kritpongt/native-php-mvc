<?php
declare(strict_types=1);

namespace App\Models;

// pure data - no behavior, no DB. Repository builds these from rows
final class User
{
	public function __construct(
		public readonly int $id,
		public readonly string $email,
		public readonly string $passwordHash,
		public readonly string $name,
		public readonly bool $isActive
	){}
}