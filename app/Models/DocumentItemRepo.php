<?php
declare(strict_types=1);

namespace App\Models;

use Core\Database;

class DocumentItemRepo
{
	public function __construct(private readonly Database $db){}

	/** @return list<DocumentItem> */
	public function findByDocumentId(int $documentId): array
	{
		$rows = $this->db->select(
			'SELECT * FROM document_items WHERE document_id = :document_id ORDER BY id ASC',
			['document_id' => $documentId]
		);
		
		return array_map($this->map(...), $rows);
	}

	/** @param array<string, mixed> $data */
	public function create(array $data): void
	{
		$now = date('Y-m-d H:i:s');
		
		$this->db->execute(
			'INSERT INTO document_items(
				document_id, name, description, quantity, unit_price, total_price, created_at, updated_at
			) VALUES (
				:document_id, :name, :description, :quantity, :unit_price, :total_price, :created_at, :updated_at
			)',
			[
				'document_id' => $data['document_id'],
				'name' => $data['name'],
				'description' => $data['description'] ?? null,
				'quantity' => $data['quantity'] ?? 1.00,
				'unit_price' => $data['unit_price'] ?? 0.00,
				'total_price' => $data['total_price'] ?? 0.00,
				'created_at' => $now,
				'updated_at' => $now
			]
		);
	}

	/** @param array<string, mixed> $row */
	private function map(array $row): DocumentItem
	{
		return new DocumentItem(
			id: (int) $row['id'],
			document_id: (int) $row['document_id'],
			name: (string) $row['name'],
			description: $row['description'] !== null ? (string) $row['description'] : null,
			quantity: (float) $row['quantity'],
			unit_price: (float) $row['unit_price'],
			total_price: (float) $row['total_price'],
			created_at: (string) $row['created_at'],
			updated_at: (string) $row['updated_at']
		);
	}
}
