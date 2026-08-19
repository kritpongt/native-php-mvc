<?php
declare(strict_types=1);

use Core\Database;

return new class{
	public function up(Database $db): void
	{
		$db->raw(match($db->driver()){
			'mysql' => 'CREATE TABLE documents(
				id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
				document_no VARCHAR(50) NOT NULL UNIQUE,
				customer_id BIGINT UNSIGNED NOT NULL,
				type VARCHAR(50) NOT NULL, /* quotation, delivery, invoice */
				status VARCHAR(50) NOT NULL, /* draft, issued, accepted, paid, cancelled */
				issue_date DATE NOT NULL,
				due_date DATE DEFAULT NULL,
				reference_id BIGINT UNSIGNED DEFAULT NULL,
				subtotal DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
				discount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
				vat DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
				grand_total DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
				notes TEXT DEFAULT NULL,
				created_at DATETIME NOT NULL,
				updated_at DATETIME NOT NULL,
				FOREIGN KEY (customer_id) REFERENCES customers(id),
				FOREIGN KEY (reference_id) REFERENCES documents(id)
			)',
			'pgsql' => 'CREATE TABLE documents(
				id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
				document_no VARCHAR(50) NOT NULL UNIQUE,
				customer_id BIGINT NOT NULL,
				type VARCHAR(50) NOT NULL,
				status VARCHAR(50) NOT NULL,
				issue_date DATE NOT NULL,
				due_date DATE DEFAULT NULL,
				reference_id BIGINT DEFAULT NULL,
				subtotal DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
				discount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
				vat DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
				grand_total DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
				notes TEXT DEFAULT NULL,
				created_at TIMESTAMP NOT NULL,
				updated_at TIMESTAMP NOT NULL,
				FOREIGN KEY (customer_id) REFERENCES customers(id),
				FOREIGN KEY (reference_id) REFERENCES documents(id)
			)'
		});
	}

	public function down(Database $db): void
	{
		$db->raw('DROP TABLE IF EXISTS documents');
	}
};
