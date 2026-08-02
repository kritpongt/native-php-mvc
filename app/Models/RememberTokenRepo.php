<?php
declare(strict_types=1);

namespace App\Models;

use Core\Database;
use DateTimeImmutable;

class RememberTokenRepo
{
	public function __construct(private readonly Database $db){}

	/** returns expired rows too - AuthService decides what expiry means */
	public function findBySelector(string $selector): ?RememberToken
	{
		$row = $this->db->selectOne(
			'SELECT id, user_id, selector, validator_hash, expires_at
			FROM remember_tokens WHERE selector = :selector',
			['selector' => $selector]
		);

		return $row === null ? null : $this->map($row);
	}

	public function create(int $userId, string $selector, string $validatorHash, string $expiresAt): void
	{
		$now = date('Y-m-d H:i:s');

		$this->db->execute(
			'INSERT INTO remember_tokens(user_id, selector, validator_hash, expires_at, created_at, updated_at)
			VALUES(:user_id, :selector, :validator_hash, :expires_at, :created_at, :updated_at)',
			[
				'user_id' => $userId,
				'selector' => $selector,
				'validator_hash' => $validatorHash,
				'expires_at' => $expiresAt,
				'created_at' => $now,
				'updated_at' => $now
			]
		);
	}

	/** token rotation: old row dies, new row born */
	public function deleteBySelector(string $selector): void
	{
		$this->db->execute(
			'DELETE FROM remember_tokens WHERE selector = :selector',
			['selector' => $selector]
		);
	}

	/** theft response: kill every device's token for this user */
	public function deleteForUser(int $userId): void
	{
		$this->db->execute(
			'DELETE FROM remember_tokens WHERE user_id = :user_id',
			['user_id' => $userId]
		);
	}

	public function purgeExpired(): void
	{
		$this->db->execute(
			'DELETE FROM remember_tokens WHERE expires_at < :now',
			['now' => date('Y-m-d H:i:s')]
		);
	}

	/** @param array<string, mixed> $row */
	private function map(array $row): RememberToken
	{
		return new RememberToken(
			id: (int) $row['id'],
			userId: (int) $row['user_id'],
			selector: (string) $row['selector'],
			validatorHash: (string) $row['validator_hash'],
			expiresAt: new DateTimeImmutable((string) $row['expires_at'])
		);
	}
}