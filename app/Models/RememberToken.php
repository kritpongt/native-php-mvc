<?php
declare(strict_types=1);

namespace App\Models;

use DateTimeImmutable;

final class RememberToken
{
	public function __construct(
		public readonly int $id,
		public readonly int $userId,
		public readonly string $selector,
		public readonly string $validatorHash,
		public readonly DateTimeImmutable $expiresAt
	){}
}