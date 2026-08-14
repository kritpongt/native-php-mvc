<?php
declare(strict_types=1);

namespace App\Models;

use Core\Database;

class UserRepo
{
	public function __construct(private readonly Database $db){}

	public function findAll(): array
	{
		$rows = $this->db->select('SELECT * FROM users ORDER BY id DESC');

		return array_map(fn(array $row) => $this->map($row), $rows);
	}

	public function findByEmail(string $email): ?User
	{
		$row = $this->db->selectOne(
			'SELECT id, email, password_hash, name, is_active FROM users WHERE email = :email',
			['email' => $email]
		);

		return $row === null ? null : $this->map($row);
	}
	
	public function findByEmailExceptId(string $email, int $excludeId): ?User
	{
		$row = $this->db->selectOne(
			'SELECT id, email, password_hash, name, is_active FROM users WHERE email = :email AND id != :id',
			['email' => $email, 'id' => $excludeId]
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

	public function create(string $email, string $passwordHash, string $name, bool $isActive = true): User
	{
		$now = date('Y-m-d H:i:s');

		$this->db->execute(
			'INSERT INTO users(email, password_hash, name, is_active, created_at, updated_at)
			VALUES(:email, :password_hash, :name, :is_active, :created_at, :updated_at)',
			[
				'email' => $email,
				'password_hash' => $passwordHash,
				'name' => $name,
				'is_active' => $isActive ? 1 : 0, // '1' is valid boolean input on pgsql, valid TINYINT on mysql
				'created_at' => $now,
				'updated_at' => $now
			]
		);

		return new User(
			id: $this->db->lastInsertId(),
			email: $email,
			passwordHash: $passwordHash,
			name: $name,
			is_active: $isActive,
		);
	}

	public function update(int $id, string $email, string $name, bool $isActive, ?string $passwordHash = null): void
	{
		$now = date('Y-m-d H:i:s');
		$params = [
			'id' => $id,
			'email' => $email,
			'name' => $name,
			'is_active' => $isActive ? 1 : 0,
			'updated_at' => $now
		];

		$passwordSql = '';
		if ($passwordHash !== null) {
			$passwordSql = 'password_hash = :password_hash, ';
			$params['password_hash'] = $passwordHash;
		}

		$this->db->execute(
			"UPDATE users SET email = :email, name = :name, is_active = :is_active, {$passwordSql} updated_at = :updated_at WHERE id = :id",
			$params
		);
	}

	public function delete(int $id): void
	{
		$this->db->execute('DELETE FROM users WHERE id = :id', ['id' => $id]);
	}

	public function paginate(array $filters = [], int $page = 1, int $perPage = 15): array
	{
		$search = (string) ($filters['search'] ?? '');
		$role = (string) ($filters['role'] ?? '');
		$isActive = (string) ($filters['isActive'] ?? '');

		$where = [];
		$params = [];

		if($search !== ''){
			$where[] = '(name LIKE :search OR email LIKE :search)';
			$params['search'] = "%{$search}%";
		}
		if($isActive === 'active'){
			$where[] = 'u.is_active = true';
		}else if($isActive === 'inactive'){
			$where[] = 'u.is_active = false';
		}

		$joinSql = '';
		if($role !== ''){
			$joinSql = ' INNER JOIN user_roles ur ON ur.user_id = u.id INNER JOIN roles r ON r.id = ur.role_id ';
			$where[] = 'r.name = :role';
			$params['role'] = $role;
		}

		$sqlWhere = $where ? ' WHERE '.implode(' AND ', $where): '';

		$total = (int) ($this->db->selectOne("SELECT COUNT(DISTINCT u.id) as aggregate FROM users u {$joinSql} {$sqlWhere}", $params))['aggregate'] ?? 0;

		$offset = ($page - 1) * $perPage;
		$params['limit'] = $perPage;
		$params['offset'] = $offset;

		$rows = $this->db->select("SELECT DISTINCT u.id, u.email, u.password_hash, u.name, u.is_active, u.created_at
			FROM users u {$joinSql} {$sqlWhere}
			ORDER BY u.id DESC
			LIMIT :limit OFFSET :offset", $params
		);

		if(empty($rows)){
			return ['items' => [], 'total' => $total];
		}

		$userIds = array_column($rows, 'id');
		$placeholders = implode(',', array_fill(0, count($userIds), '?')); // [?, ?, ?]

		$roleRows = $this->db->select("SELECT ur.user_id, r.name as role_name 
			FROM user_roles ur 
			INNER JOIN roles r ON r.id = ur.role_id 
			WHERE ur.user_id IN ({$placeholders})",
			$userIds
    );

		$rolesByUserId = [];
    foreach ($roleRows as $rRow) {
			$rolesByUserId[$rRow['user_id']][] = $rRow['role_name'];
    }

		$items = array_map(function(array $row) use ($rolesByUserId){
			$row['roles'] = $rolesByUserId[$row['id']] ?? [];
			$row['role_names'] = implode(', ', $row['roles']);
			return $this->map($row);
		}, $rows);

		return [
			'items' => $items,
			'total'	=> $total
		];
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
			is_active: (bool) $row['is_active'],
			created_at: $row['created_at'] ?? '',
			roles: $row['roles'] ?? null,
			role_names: $row['role_names'] ?? null
		);
	}
}