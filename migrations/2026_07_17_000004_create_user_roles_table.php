<?php
declare(strict_types=1);

use Core\Database;

return new class{
	public function up(Database $db): void
	{
		$db->raw(match($db->driver()){
			'mysql' => 'CREATE TABLE user_roles(
				user_id BIGINT UNSIGNED NOT NULL,
				role_id BIGINT UNSIGNED NOT NULL,
				created_at DATETIME NOT NULL,
				updated_at DATETIME NOT NULL,
				PRIMARY KEY(user_id, role_id),
				FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
				FOREIGN KEY(role_id) REFERENCES roles(id) ON DELETE CASCADE
			)',
			'pgsql' => 'CREATE TABLE user_roles(
				user_id BIGINT NOT NULL,
				role_id BIGINT NOT NULL,
				created_at TIMESTAMP NOT NULL,
				updated_at TIMESTAMP NOT NULL,
				PRIMARY KEY(user_id, role_id),
				FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
				FOREIGN KEY(role_id) REFERENCES roles(id) ON DELETE CASCADE
			)'
		});

		if($db->driver() === 'pgsql'){
			$db->raw('CREATE INDEX user_roles_role_id_idx ON user_roles(role_id)');
		}
	}

	public function down(Database $db): void
	{
		$db->raw('DROP TABLE IF EXISTS user_roles');
	}
};