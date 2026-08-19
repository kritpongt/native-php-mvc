<?php
declare(strict_types=1);

namespace App\Models;

use Core\Database;

class CustomerRepo
{
	public function __construct(private readonly Database $db){}

	public function findById(int $id): ?Customer
	{
		$row = $this->db->selectOne(
			'SELECT * FROM customers WHERE id = :id',
			['id' => $id]
		);

		return $row === null ? null : $this->map($row);
	}

	/** @return list<Customer> */
	public function all(): array
	{
		$rows = $this->db->select('SELECT * FROM customers ORDER BY name');

		return array_map($this->map(...), $rows);
	}

	public function create(string $name, ?string $tax_id, ?string $address, ?string $phone, ?string $email): Customer
	{
		$now = date('Y-m-d H:i:s');

		$this->db->execute(
			'INSERT INTO customers(name, tax_id, address, phone, email, created_at, updated_at) 
			VALUES(:name, :tax_id, :address, :phone, :email, :created_at, :updated_at)',
			[
				'name' => $name, 
				'tax_id' => $tax_id, 
				'address' => $address, 
				'phone' => $phone, 
				'email' => $email, 
				'created_at' => $now, 
				'updated_at' => $now
			]
		);

		return new Customer(
			id: $this->db->lastInsertId(), 
			name: $name, 
			tax_id: $tax_id, 
			address: $address, 
			phone: $phone, 
			email: $email, 
			created_at: $now, 
			updated_at: $now
		);
	}

	/** @param array<string, mixed> $row */
	private function map(array $row): Customer
	{
		return new Customer(
			id: (int) $row['id'],
			name: (string) $row['name'],
			tax_id: $row['tax_id'] !== null ? (string) $row['tax_id'] : null,
			address: $row['address'] !== null ? (string) $row['address'] : null,
			phone: $row['phone'] !== null ? (string) $row['phone'] : null,
			email: $row['email'] !== null ? (string) $row['email'] : null,
			created_at: (string) $row['created_at'],
			updated_at: (string) $row['updated_at']
		);
	}
}
