<?php
declare(strict_types=1);

namespace App\Models;

final class DocumentItem
{
	public function __construct(
		public readonly int $id,
		public readonly int $document_id,
		public readonly ?int $product_id,
		public readonly string $name,
		public readonly ?string $description,
		public readonly float $quantity,
		public readonly float $unit_price,
		public readonly float $labor_price,
		public readonly float $total_price,
		public readonly string $created_at = '',
		public readonly string $updated_at = ''
	){}
}