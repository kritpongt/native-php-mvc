<?php
declare(strict_types=1);

use Core\Database;

return new class{
	public function up(Database $db): void
	{
		$db->raw(match($db->driver()){
			'mysql' => 'CREATE TABLE audit_logs(
				id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
				user_id BIGINT UNSIGNED NULL,
				action VARCHAR(50) NOT NULL,
				table_name VARCHAR(100) NOT NULL,
				record_id BIGINT NOT NULL,
				old_values JSON NULL,
				new_values JSON NULL,
				ip_address VARCHAR(45) NULL,
				created_at DATETIME NOT NULL,
				INDEX audit_logs_table_record_idx (table_name, record_id),
				INDEX audit_logs_user_id_idx (user_id)
			)',
			'pgsql' => 'CREATE TABLE audit_logs(
				id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
				user_id BIGINT NULL,
				action VARCHAR(50) NOT NULL,
				table_name VARCHAR(100) NOT NULL,
				record_id BIGINT NOT NULL,
				old_values JSON NULL,
				new_values JSON NULL,
				ip_address VARCHAR(45) NULL,
				created_at TIMESTAMP NOT NULL
			);
			CREATE INDEX audit_logs_table_record_idx ON audit_logs (table_name, record_id);
			CREATE INDEX audit_logs_user_id_idx ON audit_logs (user_id);'
		});
	}

	public function down(Database $db): void
	{
		$db->raw('DROP TABLE IF EXISTS audit_logs');
	}
};