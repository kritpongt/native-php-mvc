<?php
declare(strict_types=1);

use Core\Database;

return new class{
	public function up(Database $db): void
	{
		$db->raw(match($db->driver()){
			'mysql' => 'CREATE TABLE roles(
				id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
				name VARCHAR(100) NOT NULL UNIQUE,
				created_at DATETIME NOT NULL,
				updated_at DATETIME NOT NULL
			)',
			'pgsql' => 'CREATE TABLE roles(
				id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
				name VARCHAR(100) NOT NULL UNIQUE,
				created_at TIMESTAMP NOT NULL,
				updated_at TIMESTAMP NOT NULL
			)'
		});
	}

	public function down(Database $db): void
	{
		$db->raw('DROP TABLE IF EXISTS roles');
	}
};