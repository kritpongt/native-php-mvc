<?php
declare(strict_types=1);

namespace App\Models;

// pure data - no behavior, no DB. Repository builds these from rows
final class Customer
{
	public function __construct(
		public readonly int $id,
		public readonly string $name,
		public readonly ?string $tax_id,
		public readonly ?string $address,
		public readonly ?string $phone,
		public readonly ?string $email,
		public readonly string $created_at = '',
		public readonly string $updated_at = ''
	){}
}
