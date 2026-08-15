<?php
declare(strict_types=1);

namespace App\Models;

use Core\Database;

class RoleRepo
{
	public function __construct(private readonly Database $db){}

	public function findByName(string $name): ?Role
	{
		$row = $this->db->selectOne(
			'SELECT id, name FROM roles WHERE name = :name',
			['name' => $name]
		);

		return $row === null ? null : $this->map($row);
	}

	public function findById(int $id): ?Role
	{
		$row = $this->db->selectOne(
			'SELECT id, name FROM roles WHERE id = :id',
			['id' => $id]
		);

		return $row === null ? null : $this->map($row);
	}

	public function findByNameExceptId(string $name, int $id): ?Role
	{
		$row = $this->db->selectOne(
			'SELECT id, name FROM roles WHERE name = :name AND id != :id',
			['name' => $name, 'id' => $id]
		);

		return $row === null ? null : $this->map($row);
	}

	/** @return list<Role> */
	public function all(): array
	{
		$rows = $this->db->select('SELECT id, name FROM roles ORDER BY id');

		return array_map($this->map(...), $rows);
	}

	public function create(string $name): Role
	{
		$now = date('Y-m-d H:i:s');

		$this->db->execute(
			'INSERT INTO roles(name, created_at, updated_at) VALUES(:name, :created_at, :updated_at)',
			['name' => $name, 'created_at' => $now, 'updated_at' => $now]
		);

		return new Role(id: $this->db->lastInsertId(), name: $name);
	}

	public function update(int $id, string $name): void
	{
		$this->db->execute(
			'UPDATE roles SET name = :name, updated_at = :updated_at WHERE id = :id',
			['id' => $id, 'name' => $name, 'updated_at' => date('Y-m-d H:i:s')]
		);
	}

	/** pivots die with it - ON DELETE CASCADE */
	public function delete(int $id): void
	{
		$this->db->execute('DELETE FROM roles WHERE id = :id', ['id' => $id]);
	}

	public function revokeAllFromUser(int $userId): void
	{
		$this->db->execute('DELETE FROM user_roles WHERE user_id = :user_id', ['user_id' => $userId]);
	}

	/** idempotent: assigning twice is a no-op, not a PK explosion */
	public function assignToUser(int $userId, int $roleId): void
	{
		$exists = $this->db->selectOne(
			'SELECT user_id FROM user_roles WHERE user_id = :user_id AND role_id = :role_id',
			['user_id' => $userId, 'role_id' => $roleId]
		);

		if($exists !== null){ return; }

		$now = date('Y-m-d H:i:s');

		$this->db->execute(
			'INSERT INTO user_roles(user_id, role_id, created_at, updated_at)
			VALUES(:user_id, :role_id, :created_at, :updated_at)',
			['user_id' => $userId, 'role_id' => $roleId, 'created_at' => $now, 'updated_at' => $now]
		);
	}

	/** @param array<string, mixed> $row */
	private function map(array $row): Role
	{
		return new Role(id: (int) $row['id'], name: (string) $row['name']);
	}
}