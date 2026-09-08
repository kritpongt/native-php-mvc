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

	public function findByReferenceId(int $id): ?Document
	{
		$row = $this->db->selectOne('SELECT * FROM documents WHERE reference_id = :reference_id', ['reference_id' => $id]);
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

	public function findByIdAndType(int $id, string $type): ?Document
	{
		$row = $this->db->selectOne(
			'SELECT * FROM documents WHERE id = :id AND type = :type',
			[
				'id' => $id,
				'type' => $type
			]
		);
		return $row === null ? null : $this->map($row);
	}

	/** @param array<string, mixed> $data */
	public function create(array $data): Document
	{
		$now = date('Y-m-d H:i:s');
		
		$this->db->execute(
			'INSERT INTO documents(
				document_no, customer_id, type, status, issue_date, due_date, reference_id, 
				subtotal, discount, vat, grand_total, title, notes, created_at, updated_at
			) VALUES (
				:document_no, :customer_id, :type, :status, :issue_date, :due_date, :reference_id, 
				:subtotal, :discount, :vat, :grand_total, :title, :notes, :created_at, :updated_at
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
				'title' => $data['title'] ?? null,
				'notes' => $data['notes'] ?? null,
				'created_at' => $now,
				'updated_at' => $now
			]
		);

		return clone $this->findById((int) $this->db->lastInsertId());
	}

	public function updateStatus(int $id, string $status): void
	{
		$now = date('Y-m-d H:i:s');

		$this->db->execute(
			'UPDATE documents SET status = :status, updated_at = :updated_at WHERE id = :id',
			[
				'id' => $id,
				'status' => $status,
				'updated_at' => $now
			]
		);
	}

	public function update(int $id, array $data): void
	{
		$now = date('Y-m-d H:i:s');
		$this->db->execute(
			'UPDATE documents SET
				customer_id = :customer_id,
				issue_date = :issue_date,
				due_date = :due_date,
				discount = :discount,
				vat = :vat,
				subtotal = :subtotal,
				grand_total = :grand_total,
				title = :title,
				notes = :notes,
				updated_at = :updated_at 
			WHERE id = :id',
			[
				'id' => $id,
				'customer_id' => $data['customer_id'],
				'issue_date' => $data['issue_date'],
				'due_date' => $data['due_date'] ?? null,
				'discount' => $data['discount'] ?? 0.00,
				'vat' => $data['vat'] ?? 0.00,
				'subtotal' => $data['subtotal'] ?? 0.00,
				'grand_total' => $data['grand_total'] ?? 0.00,
				'title' => $data['title'] ?? null,
				'notes' => $data['notes'] ?? null,
				'updated_at' => $now,
			]
		);
	}

	public function generateNo(string $type): string
	{
		$prefix = match($type){
			'quotation' => 'QU',
			// 'delivery' => 'DO',
			'invoice' => 'INV',
			default => 'DOC'
		};

		$yearMonth = date('Ym'); // e.g., 202608
		$searchPrefix = "{$prefix}-{$yearMonth}-%";

		// Find the latest document number for this month
		$row = $this->db->selectOne(
			'SELECT document_no FROM documents WHERE document_no LIKE :prefix ORDER BY id DESC LIMIT 1',
			['prefix' => $searchPrefix]
		);

		if($row === null){
			return "{$prefix}-{$yearMonth}-001";
		}

		// Example: QU-202608-001 -> Extract '001' and increment
		$parts = explode('-', (string) $row['document_no']);
		$lastNumber = (int) end($parts);
		$newNumber = str_pad((string) ($lastNumber + 1), 3, '0', STR_PAD_LEFT);

		return "{$prefix}-{$yearMonth}-{$newNumber}";
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
			title: $row['title'] !== null ? (string) $row['title'] : null,
			notes: $row['notes'] !== null ? (string) $row['notes'] : null,
			created_at: (string) $row['created_at'],
			updated_at: (string) $row['updated_at']
		);
	}
}
