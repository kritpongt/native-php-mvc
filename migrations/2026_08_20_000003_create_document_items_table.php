<?php
declare(strict_types=1);

use Core\Database;

return new class{
	public function up(Database $db): void
	{
		$db->raw(match($db->driver()){
			'mysql' => 'CREATE TABLE document_items(
				id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
				document_id BIGINT UNSIGNED NOT NULL,
				name VARCHAR(255) NOT NULL,
				description TEXT DEFAULT NULL,
				quantity DECIMAL(10, 2) NOT NULL DEFAULT 1.00,
				unit_price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
				total_price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
				created_at DATETIME NOT NULL,
				updated_at DATETIME NOT NULL,
				FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE
			)',
			'pgsql' => 'CREATE TABLE document_items(
				id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
				document_id BIGINT NOT NULL,
				name VARCHAR(255) NOT NULL,
				description TEXT DEFAULT NULL,
				quantity DECIMAL(10, 2) NOT NULL DEFAULT 1.00,
				unit_price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
				total_price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
				created_at TIMESTAMP NOT NULL,
				updated_at TIMESTAMP NOT NULL,
				FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE
			)'
		});
	}

	public function down(Database $db): void
	{
		$db->raw('DROP TABLE IF EXISTS document_items');
	}
};
