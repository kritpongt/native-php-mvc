<?php
declare(strict_types=1);

namespace Core;

final class Csrf
{
	private const SESSION_KEY = '_csrf_token';

	public function __construct(private readonly SessionInterface $session){}

	/** one token per session: created on first ask, then reused */
	public function token(): string
	{
		$token = $this->session->get(self::SESSION_KEY);

		if(!is_string($token) || $token === ''){
			// random_bytes = CSPRNG (crypto-grade random) -> 64 hex chars
			$token = bin2hex(random_bytes(32));
			$this->session->set(self::SESSION_KEY, $token);
		}

		return $token;
	}

	/** project rule: hash_equals for every token compare - timing-safe */
	public function verify(?string $given): bool
	{
		$known = $this->session->get(self::SESSION_KEY);

		if(!is_string($known) || !is_string($given) || $given === ''){ return false; }

		return hash_equals($known, $given);
	}
}