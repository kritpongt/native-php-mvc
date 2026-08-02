<?php
declare(strict_types=1);

namespace App\Models;

use Core\Database;

class UserRepo
{
	public function __construct(private readonly Database $db){}

	public function findByEmail(string $email): ?User
	{
		$row = $this->db->selectOne(
			'SELECT id, email, password_hash, name, is_active FROM users WHERE email = :email',
			['email' => $email]
		);

		return $row === null ? null : $this->map($row);
	}

	public function findById(int $id): ?User
	{
		$row = $this->db->selectOne(
			'SELECT id, email, password_hash, name, is_active FROM users WHERE id = :id',
			['id' => $id]
		);

		return $row === null ? null : $this->map($row);
	}

	public function create(string $email, string $passwordHash, string $name): User
	{
		$now = date('Y-m-d H:i:s');

		$this->db->execute(
			'INSERT INTO users(email, password_hash, name, is_active, created_at, updated_at)
			VALUES(:email, :password_hash, :name, :is_active, :created_at, :updated_at)',
			[
				'email' => $email,
				'password_hash' => $passwordHash,
				'name' => $name,
				'is_active' => 1, // '1' is valid boolean input on pgsql, valid TINYINT on mysql
				'created_at' => $now,
				'updated_at' => $now
			]
		);

		return new User(
			id: $this->db->lastInsertId(),
			email: $email,
			passwordHash: $passwordHash,
			name: $name,
			isActive: true
		);
	}

	/** @param array<string, mixed> $row */
	private function map(array $row): User
	{
		return new User(
			id: (int) $row['id'],
			email: (string) $row['email'],
			passwordHash: (string) $row['password_hash'],
			name: (string) $row['name'],
			// mysql returns int, pgsql returns bool - cast unifies both
			isActive: (bool) $row['is_active']
		);
	}
}