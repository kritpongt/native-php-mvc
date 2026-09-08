<?php
declare(strict_types=1);

use Core\Database;

return new class{
	public function up(Database $db): void
	{
		$db->raw(match($db->driver()){
			'mysql' => 'CREATE TABLE products(
				id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
				sku VARCHAR(100) DEFAULT NULL,
				name VARCHAR(255) NOT NULL,
				description TEXT DEFAULT NULL,
				unit_price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
				labor_price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
				created_at DATETIME NOT NULL,
				updated_at DATETIME NOT NULL
			)',
			'pgsql' => 'CREATE TABLE products(
				id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
				sku VARCHAR(100) DEFAULT NULL,
				name VARCHAR(255) NOT NULL,
				description TEXT DEFAULT NULL,
				unit_price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
				labor_price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
				created_at TIMESTAMP NOT NULL,
				updated_at TIMESTAMP NOT NULL
			)'
		});
	}

	public function down(Database $db): void
	{
		$db->raw('DROP TABLE IF EXISTS products');
	}
};