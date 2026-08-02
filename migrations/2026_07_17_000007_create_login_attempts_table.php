<?php
declare(strict_types=1);

use Core\Database;

return new class{
	public function up(Database $db): void
	{
		// no FK to users on purpose: failed logins for unknown emails must count too.
		// ip VARCHAR(45) = IPv6 max length.
		// created_at IS the attempt time - no separate attempted_at column
		$db->raw(match($db->driver()){
			'mysql' => 'CREATE TABLE login_attempts(
				id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
				email VARCHAR(255) NOT NULL,
				ip VARCHAR(45) NOT NULL,
				created_at DATETIME NOT NULL,
				updated_at DATETIME NOT NULL
			)',
			'pgsql' => 'CREATE TABLE login_attempts(
				id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
				email VARCHAR(255) NOT NULL,
				ip VARCHAR(45) NOT NULL,
				created_at TIMESTAMP NOT NULL,
				updated_at TIMESTAMP NOT NULL
			)'
		});

		// the rate-limit query: WHERE email=? AND ip=? AND created_at > ?
		$db->raw('CREATE INDEX login_attempts_email_ip_created_idx ON login_attempts(email, ip, created_at)');
	}

	public function down(Database $db): void
	{
		$db->raw('DROP TABLE IF EXISTS login_attempts');
	}
};