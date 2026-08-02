<?php
declare(strict_types=1);

namespace App\Models;

use Core\Database;

// no entity: rows never leave this class, service only needs counts
class LoginAttemptRepo
{
	public function __construct(private readonly Database $db){}

	/** $since format 'Y-m-d H:i:s' - AuthService computes window start */
	public function countSince(string $email, string $ip, string $since): int
	{
		$row = $this->db->selectOne(
			'SELECT COUNT(*) AS attempt_count FROM login_attempts
			WHERE email = :email AND ip = :ip AND created_at > :since',
			['email' => $email, 'ip' => $ip, 'since' => $since]
		);

		// mysql returns COUNT as string, pgsql as int - cast unifies
		return (int) ($row['attempt_count'] ?? 0);
	}

	public function record(string $email, string $ip): void
	{
		$now = date('Y-m-d H:i:s');

		$this->db->execute(
			'INSERT INTO login_attempts(email, ip, created_at, updated_at)
			VALUES(:email, :ip, :created_at, :updated_at)',
			['email' => $email, 'ip' => $ip, 'created_at' => $now, 'updated_at' => $now]
		);
	}

	/** successful login wipes the counter for that email+ip pair */
	public function clear(string $email, string $ip): void
	{
		$this->db->execute(
			'DELETE FROM login_attempts WHERE email = :email AND ip = :ip',
			['email' => $email, 'ip' => $ip]
		);
	}

	/** called on each record() - table cleans itself, no cron needed */
	public function purgeBefore(string $before): void
	{
		$this->db->execute(
			'DELETE FROM login_attempts WHERE created_at < :before',
			['before' => $before]
		);
	}
}