<?php
declare(strict_types=1);

use Core\Database;

return new class{
	public function up(Database $db): void
	{
		$db->raw(match($db->driver()){
			'mysql' => 'CREATE TABLE customers(
				id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
				name VARCHAR(255) NOT NULL,
				tax_id VARCHAR(50) DEFAULT NULL,
				address TEXT DEFAULT NULL,
				phone VARCHAR(50) DEFAULT NULL,
				email VARCHAR(255) DEFAULT NULL,
				created_at DATETIME NOT NULL,
				updated_at DATETIME NOT NULL
			)',
			'pgsql' => 'CREATE TABLE customers(
				id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
				name VARCHAR(255) NOT NULL,
				tax_id VARCHAR(50) DEFAULT NULL,
				address TEXT DEFAULT NULL,
				phone VARCHAR(50) DEFAULT NULL,
				email VARCHAR(255) DEFAULT NULL,
				created_at TIMESTAMP NOT NULL,
				updated_at TIMESTAMP NOT NULL
			)'
		});
	}

	public function down(Database $db): void
	{
		$db->raw('DROP TABLE IF EXISTS customers');
	}
};
