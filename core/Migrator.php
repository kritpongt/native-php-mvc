<?php
declare(strict_types=1);

namespace Core;

use RuntimeException;

final class Migrator
{
	public function __construct(
		private readonly Database $db,
		private readonly string $migrationsPath,
	){}

	/** @return list<string> names of migrations applied this run */
	public function migrate(): array
	{
		$this->ensureTable();

		// names already applied - skip them
		$done = array_column($this->db->select('SELECT migration FROM migrations'), 'migration');
	
		$batch = $this->nextBatch();
		$applied = [];

		foreach($this->files() as $file){
			$name = basename($file, '.php');

			if(in_array($name, $done, true)){ continue; }

			// migration file returns an anonymous class instance
			$migration = require $file;
			$migration->up($this->db);

			$this->db->execute(
				'INSERT INTO migrations (migration, batch, created_at) VALUES (:m, :b, :c)',
				['m' => $name, 'b' => $batch, 'c' => date('Y-m-d H:i:s')]
			);

			$applied[] = $name;
		}

		return $applied;
	}

	/** undo the LAST batch only, newest file first. @return list<string> */
	public function rollback(): array
	{
		$this->ensureTable();

		$batch = (int) ($this->db->selectOne('SELECT MAX(batch) AS b FROM migrations')['b'] ?? 0);

		if($batch === 0){ return []; }

		$rows = $this->db->select(
			'SELECT migration FROM migrations WHERE batch = :b ORDER BY migration DESC',
			['b' => $batch]
		);

		$rolled = [];

		foreach($rows as $row){
			$name = (string) $row['migration'];
			$file = "{$this->migrationsPath}/{$name}.php";

			if(!is_file($file)){
				throw new RuntimeException("Migration file missing: {$name}");
			}

			$migration = require $file;
			$migration->down($this->db);

			$this->db->execute('DELETE FROM migrations WHERE migration = :m', ['m' => $name]);

			$rolled[] = $name;
		}

		return $rolled;
	}

	/** sorted by filename = sorted by date prefix = correct run order */
	private function files(): array
	{
		$files = glob($this->migrationsPath.'/*.php') ?: [];
		sort($files);

		return $files;
	}

	private function nextBatch(): int
	{
		return (int) ($this->db->selectOne('SELECT MAX(batch) AS b FROM migrations')['b'] ?? 0) + 1;
	}

	private function ensureTable(): void
	{
		// DDL differs per driver -> branching allowed HERE (never in repositories)
		$sql = match($this->db->driver()){
			'mysql' => 'CREATE TABLE IF NOT EXISTS migrations(
				id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
				migration VARCHAR(255) NOT NULL,
				batch INT NOT NULL,
				created_at DATETIME NOT NULL
			)',
			'pgsql' => 'CREATE TABLE IF NOT EXISTS migrations(
				id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
				migration VARCHAR(255) NOT NULL,
				batch INT NOT NULL,
				created_at TIMESTAMP NOT NULL
			)',
			default => throw new RuntimeException("Unsupported driver: {$this->db->driver()}")
		};

		$this->db->raw($sql);
	}
}