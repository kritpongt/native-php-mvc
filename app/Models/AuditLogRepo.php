<?php
declare(strict_types=1);

namespace App\Models;

use Core\Database;

class AuditLogRepo
{
	public function __construct(private readonly Database $db){}

	public function create(
		?int $userId,
		string $action,
		string $tableName,
		int $recordId,
		?array $oldValues = null,
		?array $newValues = null,
		?string $ipAddress = null
	): void {
		$now = date('Y-m-d H:i:s');
		
		$this->db->execute(
			'INSERT INTO audit_logs(user_id, action, table_name, record_id, old_values, new_values, ip_address, created_at)
			VALUES(:user_id, :action, :table_name, :record_id, :old_values, :new_values, :ip_address, :created_at)',
			[
				'user_id' => $userId,
				'action' => $action,
				'table_name' => $tableName,
				'record_id' => $recordId,
				'old_values' => $oldValues !== null ? json_encode($oldValues) : null,
				'new_values' => $newValues !== null ? json_encode($newValues) : null,
				'ip_address' => $ipAddress,
				'created_at' => $now
			]
		);
	}

	public function findByTableAndRecord(string $tableName, int $recordId): array
	{
		$rows = $this->db->select(
			'SELECT * FROM audit_logs WHERE table_name = :table_name AND record_id = :record_id ORDER BY id DESC',
			['table_name' => $tableName, 'record_id' => $recordId]
		);

		return array_map(fn(array $row) => $this->map($row), $rows);
	}

	/** @param array<string, mixed> $row */
	private function map(array $row): AuditLog
	{
		return new AuditLog(
			id: (int) $row['id'],
			user_id: $row['user_id'] ? (int) $row['user_id'] : null,
			action: (string) $row['action'],
			table_name: (string) $row['table_name'],
			record_id: (int) $row['record_id'],
			old_values: $row['old_values'],
			new_values: $row['new_values'],
			ip_address: $row['ip_address'],
			created_at: (string) $row['created_at']
		);
	}

	public function paginate(array $filters = [], int $page = 1, int $perPage = 15): array
	{
		$search = (string) ($filters['search'] ?? '');
		$action = (string) ($filters['action'] ?? '');
		$tableName = (string) ($filters['table_name'] ?? '');

		$where = [];
		$params = [];

		if($search !== ''){
			$where[] = '(a.action LIKE :search OR a.table_name LIKE :search OR u.name LIKE :search OR u.email LIKE :search OR a.ip_address LIKE :search)';
			$params['search'] = "%{$search}%";
		}
		if($action !== ''){
			$where[] = 'a.action = :action';
			$params['action'] = $action;
		}
		if($tableName !== ''){
			$where[] = 'a.table_name = :table_name';
			$params['table_name'] = $tableName;
		}

		$sqlWhere = $where ? ' WHERE '.implode(' AND ', $where): '';
		
		$joinSql = ' LEFT JOIN users u ON u.id = a.user_id ';

		$total = (int) ($this->db->selectOne("SELECT COUNT(a.id) as aggregate FROM audit_logs a {$joinSql} {$sqlWhere}", $params))['aggregate'] ?? 0;

		$offset = ($page - 1) * $perPage;
		$params['limit'] = $perPage;
		$params['offset'] = $offset;

		$rows = $this->db->select("SELECT a.*, u.name as user_name, u.email as user_email
			FROM audit_logs a {$joinSql} {$sqlWhere}
			ORDER BY a.id DESC
			LIMIT :limit OFFSET :offset", $params
		);

		if(empty($rows)){
			return ['items' => [], 'total' => $total];
		}

		// Since we join with users, we should inject the user name/email into the model or keep it simple.
		// Wait, AuditLog model currently doesn't have user_name or user_email. Let's return arrays or map them.
		// I will update the map function to accept user_name and user_email if I update the model,
		// but since AuditLog is an entity, maybe I should just pass them as additional properties, or we can just return raw arrays for views.
		// Let's modify the map function slightly to include user_name in a public property if we want, or just return the items mapped but the view will need user name.
		// Wait, in PHP, we can't dynamically add properties if there's no `__set`. Let's just return the array with mapped object inside it, or update AuditLog model.
		
		$items = array_map(function(array $row) {
			$log = clone $this->map($row);
			// We can attach it as dynamic properties if the class is not final and doesn't prevent it, but PHP 8.2 deprecates dynamic properties.
			// Let's just return an associative array containing the log and the user data.
			return [
				'log' => $log,
				'user_name' => $row['user_name'] ?? null,
				'user_email' => $row['user_email'] ?? null
			];
		}, $rows);

		return [
			'items' => $items,
			'total'	=> $total
		];
	}
}