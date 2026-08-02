<?php
declare(strict_types=1);

namespace App\Models;

final class Role
{
	public function __construct(
		public readonly int $id,
		public readonly string $name
	){}
}