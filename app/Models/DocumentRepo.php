<?php
declare(strict_types=1);

namespace App\Models;

use Core\Database;

class DocumentRepo
{
	public function __construct(private readonly Database $db){}

	public function findById(int $id): ?Document
	{
		$row = $this->db->selectOne('SELECT * FROM documents WHERE id = :id', ['id' => $id]);
		return $row === null ? null : $this->map($row);
	}

	/** @return list<Document> */
	public function findByType(string $type): array
	{
		$rows = $this->db->select(
			'SELECT * FROM documents WHERE type = :type ORDER BY id DESC',
			['type' => $type]
		);
		
		return array_map($this->map(...), $rows);
	}

	/** @param array<string, mixed> $data */
	public function create(array $data): Document
	{
		$now = date('Y-m-d H:i:s');
		
		$this->db->execute(
			'INSERT INTO documents(
				document_no, customer_id, type, status, issue_date, due_date, reference_id, 
				subtotal, discount, vat, grand_total, notes, created_at, updated_at
			) VALUES (
				:document_no, :customer_id, :type, :status, :issue_date, :due_date, :reference_id, 
				:subtotal, :discount, :vat, :grand_total, :notes, :created_at, :updated_at
			)',
			[
				'document_no' => $data['document_no'],
				'customer_id' => $data['customer_id'],
				'type' => $data['type'],
				'status' => $data['status'],
				'issue_date' => $data['issue_date'],
				'due_date' => $data['due_date'] ?? null,
				'reference_id' => $data['reference_id'] ?? null,
				'subtotal' => $data['subtotal'] ?? 0.00,
				'discount' => $data['discount'] ?? 0.00,
				'vat' => $data['vat'] ?? 0.00,
				'grand_total' => $data['grand_total'] ?? 0.00,
				'notes' => $data['notes'] ?? null,
				'created_at' => $now,
				'updated_at' => $now
			]
		);

		return clone $this->findById((int) $this->db->lastInsertId());
	}

	/** @param array<string, mixed> $row */
	private function map(array $row): Document
	{
		return new Document(
			id: (int) $row['id'],
			document_no: (string) $row['document_no'],
			customer_id: (int) $row['customer_id'],
			type: (string) $row['type'],
			status: (string) $row['status'],
			issue_date: (string) $row['issue_date'],
			due_date: $row['due_date'] !== null ? (string) $row['due_date'] : null,
			reference_id: $row['reference_id'] !== null ? (int) $row['reference_id'] : null,
			subtotal: (float) $row['subtotal'],
			discount: (float) $row['discount'],
			vat: (float) $row['vat'],
			grand_total: (float) $row['grand_total'],
			notes: $row['notes'] !== null ? (string) $row['notes'] : null,
			created_at: (string) $row['created_at'],
			updated_at: (string) $row['updated_at']
		);
	}
}
