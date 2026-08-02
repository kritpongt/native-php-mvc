<?php
declare(strict_types=1);

use Core\Database;

// anonymous class: filename IS the identity, no class bookkeeping
return new class{
	public function up(Database $db): void
	{
		$db->raw(match($db->driver()){
			'mysql' => 'CREATE TABLE users(
				id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
				email VARCHAR(255) NOT NULL UNIQUE,
				password_hash VARCHAR(255) NOT NULL,
				created_at DATETIME NOT NULL,
				updated_at DATETIME NOT NULL
			)',
			'pgsql' => 'CREATE TABLE users(
				id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
				email VARCHAR(255) NOT NULL UNIQUE,
				password_hash VARCHAR(255) NOT NULL,
				created_at TIMESTAMP NOT NULL,
				updated_at TIMESTAMP NOT NULL
			)'
		});
	}

	public function down(Database $db): void
	{
		$db->raw('DROP TABLE IF EXISTS users');
	}
};