<?php
declare(strict_types=1);

namespace App\Models;

final class Document
{
	public function __construct(
		public readonly int $id,
		public readonly string $document_no,
		public readonly int $customer_id,
		public readonly string $type,
		public readonly string $status,
		public readonly string $issue_date,
		public readonly ?string $due_date,
		public readonly ?int $reference_id,
		public readonly float $subtotal,
		public readonly float $discount,
		public readonly float $vat,
		public readonly float $grand_total,
		public readonly ?string $notes,
		public readonly string $created_at = '',
		public readonly string $updated_at = '',
		
		/** add-ons (populated by repository or service) */
		public readonly ?Customer $customer = null,
		/** @var list<DocumentItem>|null */
		public readonly ?array $items = null
	){}
}
