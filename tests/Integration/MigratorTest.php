<?php
declare(strict_types=1);

namespace Tests\Integration;

use Core\Config;
use Core\Database;
use Core\Migrator;
use PHPUnit\Framework\TestCase;

final class MigratorTest extends TestCase
{
	private Database $db;
	private Migrator $migrator;

	protected function setUp(): void
	{
		$root = dirname(__DIR__, 2);

		// no Env::load here - phpunit.xml <env> supplies DB_* values,
		// Config reads them exactly like it reads .env values
		$this->db = new Database(new Config($root.'/config'));
		$this->migrator = new Migrator($this->db, $root.'/migrations');
	}

	public function test_migrate_then_rollback(): void
	{
		$applied = $this->migrator->migrate();
		$this->assertNotEmpty($applied);

		// users table exists and is empty
		$row = $this->db->selectOne('SELECT COUNT(*) AS c FROM users');
		$this->assertSame(0, (int) $row['c']);

		// running again: nothing pending - runner is idempotent
		$this->assertSame([], $this->migrator->migrate());

		// rollback undoes the batch
		$this->assertNotEmpty($this->migrator->rollback());
	}
}