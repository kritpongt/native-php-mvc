<?php
declare(strict_types=1);

use Core\Database;

return new class{
	public function up(Database $db): void
	{
		$db->raw("ALTER TABLE users
			ADD COLUMN name VARCHAR(100) NOT NULL DEFAULT '',
			ADD COLUMN is_active BOOLEAN NOT NULL DEFAULT TRUE"
		);

		$db->raw('ALTER TABLE users
			ALTER COLUMN name DROP DEFAULT,
			ALTER COLUMN is_active DROP DEFAULT'
		);
	}

	public function down(Database $db): void
	{
		$db->raw('ALTER TABLE users DROP COLUMN name, DROP COLUMN is_active');
	}
};