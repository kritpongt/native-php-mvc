<?php
declare(strict_types=1);

namespace App\Models;

use Core\Database;

class PermissionRepo
{
	public function __construct(private readonly Database $db){}

	public function findByName(string $name): ?Permission
	{
		$row = $this->db->selectOne(
			'SELECT id, name FROM permissions WHERE name = :name',
			['name' => $name]
		);

		return $row === null ? null : $this->map($row);
	}

	/** @return list<Permission> */
	public function all(): array
	{
		$rows = $this->db->select('SELECT id, name FROM permissions ORDER BY id');

		return array_map($this->map(...), $rows);
	}

	public function create(string $name): Permission
	{
		$now = date('Y-m-d H:i:s');

		$this->db->execute(
			'INSERT INTO permissions(name, created_at, updated_at) VALUES(:name, :created_at, :updated_at)',
			['name' => $name, 'created_at' => $now, 'updated_at' => $now]
		);

		return new Permission(id: $this->db->lastInsertId(), name: $name);
	}

	public function delete(int $id): void
	{
		$this->db->execute('DELETE FROM permissions WHERE id = :id', ['id' => $id]);
	}

	/** idempotent, same reason as RoleRepo::assignToUser */
	public function attachToRole(int $roleId, int $permissionId): void
	{
		$exists = $this->db->selectOne(
			'SELECT role_id FROM role_permissions WHERE role_id = :role_id AND permission_id = :permission_id',
			['role_id' => $roleId, 'permission_id' => $permissionId]
		);

		if($exists !== null){ return; }

		$now = date('Y-m-d H:i:s');

		$this->db->execute(
			'INSERT INTO role_permissions(role_id, permission_id, created_at, updated_at)
			VALUES(:role_id, :permission_id, :created_at, :updated_at)',
			['role_id' => $roleId, 'permission_id' => $permissionId, 'created_at' => $now, 'updated_at' => $now]
		);
	}

	/** rbac:sync wipes a role's pivots then re-attaches from config */
	public function detachAllFromRole(int $roleId): void
	{
		$this->db->execute('DELETE FROM role_permissions WHERE role_id = :role_id', ['role_id' => $roleId]);
	}

	/** @return list<int> */
	public function getIdsForRole(int $roleId): array
	{
		$rows = $this->db->select(
			'SELECT permission_id FROM role_permissions WHERE role_id = :role_id',
			['role_id' => $roleId]
		);
		
		return array_map(static fn(array $row): int => (int) $row['permission_id'], $rows);
	}

	/**
	 * THE authorization query - one JOIN per request, called by AuthorizationService.
	 * DISTINCT: two roles sharing a permission must not duplicate it.
	 * 
	 * @return list<string>
	 */
	public function namesForUser(int $userId): array
	{
		$rows = $this->db->select(
			'SELECT DISTINCT p.name
			FROM permissions p
			JOIN role_permissions rp ON rp.permission_id = p.id
			JOIN user_roles ur ON ur.role_id = rp.role_id
			WHERE ur.user_id = :user_id',
			['user_id' => $userId]
		);

		return array_map(static fn(array $row): string => (string) $row['name'], $rows);
	}

	/** @param array<string, mixed> $row */
	private function map(array $row): Permission
	{
		return new Permission(id: (int) $row['id'], name: (string) $row['name']);
	}
}