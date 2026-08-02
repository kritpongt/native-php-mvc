<?php
declare(strict_types=1);

use Core\Database;

return new class{
	public function up(Database $db): void
	{
		// ON DELETE CASCADE: kill a role -> its pivot rows vanish, no orphans
		$db->raw(match($db->driver()){
			'mysql' => 'CREATE TABLE role_permissions(
				role_id BIGINT UNSIGNED NOT NULL,
				permission_id BIGINT UNSIGNED NOT NULL,
				created_at DATETIME NOT NULL,
				updated_at DATETIME NOT NULL,
				PRIMARY KEY(role_id, permission_id),
				FOREIGN KEY(role_id) REFERENCES roles(id) ON DELETE CASCADE,
				FOREIGN KEY(permission_id) REFERENCES permissions(id) ON DELETE CASCADE
			)',
			'pgsql' => 'CREATE TABLE role_permissions(
				role_id BIGINT NOT NULL,
				permission_id BIGINT NOT NULL,
				created_at TIMESTAMP NOT NULL,
				updated_at TIMESTAMP NOT NULL,
				PRIMARY KEY(role_id, permission_id),
				FOREIGN KEY(role_id) REFERENCES roles(id) ON DELETE CASCADE,
				FOREIGN KEY(permission_id) REFERENCES permissions(id) ON DELETE CASCADE
			)'
		});

		// composite PK covers role_id lookups only - permission_id side needs its own.
		// mysql already made one for the FK, pgsql never auto-indexes FKs
		if($db->driver() === 'pgsql'){
			$db->raw('CREATE INDEX role_permissions_permission_id_idx ON role_permissions(permission_id)');
		}
	}

	public function down(Database $db): void
	{
		$db->raw('DROP TABLE IF EXISTS role_permissions');
	}
};