<?php
declare(strict_types=1);

use Core\Database;

return new class{
	public function up(Database $db): void
	{
		// selector: 16 random bytes hex = 32 chars, the lookup key.
		// validator_hash: sha256 hex = 64 chars - raw validator NEVER stored
		$db->raw(match($db->driver()){
			'mysql' => 'CREATE TABLE remember_tokens(
				id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
				user_id BIGINT UNSIGNED NOT NULL,
				selector VARCHAR(32) NOT NULL UNIQUE,
				validator_hash VARCHAR(64) NOT NULL,
				expires_at DATETIME NOT NULL,
				created_at DATETIME NOT NULL,
				updated_at DATETIME NOT NULL,
				FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
			)',
			'pgsql' => 'CREATE TABLE remember_tokens(
				id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
				user_id BIGINT NOT NULL,
				selector VARCHAR(32) NOT NULL UNIQUE,
				validator_hash VARCHAR(64) NOT NULL,
				expires_at TIMESTAMP NOT NULL,
				created_at TIMESTAMP NOT NULL,
				updated_at TIMESTAMP NOT NULL,
				FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
			)'
		});

		// theft response deletes ALL tokens of one user - needs this index
		if($db->driver() === 'pgsql'){
			$db->raw('CREATE INDEX remember_tokens_user_id_idx ON remember_tokens(user_id)');
		}
	}

	public function down(Database $db): void
	{
		$db->raw('DROP TABLE IF EXISTS remember_tokens');
	}
};