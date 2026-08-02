<?php
declare(strict_types=1);

namespace Core;

use Closure;
use PDO;
use RuntimeException;
use Throwable;

final class Database
{
	private ?PDO $pdo = null;

	public function __construct(private readonly Config $config){}

	/** 'mysql' or 'pgsql'. Migrations may branch on this. Repositories NEVER. */
	public function driver(): string
	{
		return (string) $this->config->get('database.driver', 'mysql');
	}

	/** lazy: connection opens on FIRST query, pages without DB pay nothing */
	private function pdo(): PDO
	{
		if($this->pdo !== null){ return $this->pdo; }

		$host = (string) $this->config->get('database.host');
		$port = (int) $this->config->get('database.port');
		$name = (string) $this->config->get('database.name');
	
		// DSN = connection string, format differs per driver
		$dsn = match($this->driver()){
			'mysql' => "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
			'pgsql' => "pgsql:host={$host};port={$port};dbname={$name}",
			default => throw new RuntimeException("Unsupported DB driver: {$this->driver()}")
		};

		$this->pdo = new PDO(
			$dsn,
			(string) $this->config->get('database.user'),
			(string) $this->config->get('database.pass'),
			[
				// fail LOUD - every SQL error throws, no silent false
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
				// real prepared statement at the server, not string-glued fakes
				PDO::ATTR_EMULATE_PREPARES => false,
				// rows as ['column' => value]
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
			]
		);

		return $this->pdo;
	}

	/**
	 * SELECT many rows.
	 * 
	 * @param array<string, mixed> $params
	 * @return list<array<string, mixed>>
	 */
	public function select(string $sql, array $params = []): array
	{
		$stmt = $this->pdo()->prepare($sql);
		$stmt->execute($params);

		return $stmt->fetchAll();
	}

	/**
	 * SELECT one row, or null when not found.
	 * 
	 * @param array<string, mixed> $params
	 * @return array<string, mixed>|null
	 */
	public function selectOne(string $sql, array $params = []): ?array
	{
		$stmt = $this->pdo()->prepare($sql);
		$stmt->execute($params);
		$row = $stmt->fetch();

		// fetch() return false on empty - we normalize to null
		return $row === false ? null : $row;
	}

	/**
	 * INSERT / UPDATE / DELETE. Returns affected row count.
	 * 
	 * @param array<string, mixed> $params
	 */
	public function execute(string $sql, array $params = []): int
	{
		$stmt = $this->pdo()->prepare($sql);
		$stmt->execute($params);

		return $stmt->rowCount();
	}

	/** driver difference lives HERE in one method - project rule */
	public function lastInsertId(): int
	{
		// mysql: last AUTO_INCREMENT | pgsql: LASTVAL() of the identity sequence
		return (int) $this->pdo()->lastInsertId();
	}

	/** all-or-nothing: any exception rolls back everything, then rethrows */
	public function transaction(Closure $fn): mixed
	{
		$pdo = $this->pdo();
		$pdo->beginTransaction();

		try{
			$result = $fn($this);
			$pdo->commit();

			return $result;
		}catch(Throwable $e){
			$pdo->rollBack();
			throw $e;
		}
	}

	/** raw DDL for the migration runner only (CREATE TABLE takes no params) */
	public function raw(string $sql): void
	{
		$this->pdo()->exec($sql);
	}
}