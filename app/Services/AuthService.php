<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\LoginAttemptRepo;
use App\Models\RememberTokenRepo;
use App\Models\User;
use App\Models\UserRepo;
use Core\Config;
use Core\SessionInterface;
use DateTimeImmutable;

final class AuthService
{
	private const SESSION_KEY = 'auth_user_id';

	// real Argon2id hash of a throwaway string. Unknown email still pays
	// one password_verify - response time cannot reveal "user exists"
	private const DUMMY_HASH ='$argon2id$v=19$m=65536,t=4,p=1$eG83cnNCRi9GRzE4N1JSag$3OHPWdY+JuOPELRT9Vwe6hMxhMONNaAUAOjEwkNE2kE';

	// per-request cache: user() may be called many times, one DB hit
	private ?User $user = null;

	public function __construct(
		private readonly UserRepo $users,
		private readonly LoginAttemptRepo $attempts,
		private readonly RememberTokenRepo $tokens,
		private readonly SessionInterface $session,
		private readonly Config $config,
	){}

	public function attempt(string $email, string $password, string $ip): LoginResult
	{
		$windowMinutes = (int) $this->config->get('auth.login_window_minutes', '15');
		$since = date('Y-m-d H:i:s', time() - $windowMinutes * 60);

		if($this->attempts->countSince($email, $ip, $since) >= (int) $this->config->get('auth.login_max_attempts', 5)){
			return LoginResult::RateLimited;
		}

		$user = $this->users->findByEmail($email);

		// ALWAYS exactly one verify per attempt - unknown email, wrong password
		// and inactive account all cost the same time
		$verified = password_verify($password, $user?->passwordHash ?? self::DUMMY_HASH);

		if($user === null || !$verified || !$user->isActive){
			$this->attempts->record($email, $ip);
			// table cleans itself, no cron job
			$this->attempts->purgeBefore($since);
			return LoginResult::Failed;
		}

		$this->attempts->clear($email, $ip);

		$this->session->regenerate();
		$this->session->set(self::SESSION_KEY, $user->id);
		$this->user = $user;

		return LoginResult::Success;
	}

	public function user(): ?User
	{
		if($this->user !== null){ return $this->user; }

		$id = $this->session->get(self::SESSION_KEY);

		if(!is_int($id)){ return null; }

		$user = $this->users->findById($id);

		if($user === null || !$user->is_active){ return null; }

		return $this->user = $user;
	}

	public function check(): bool
	{
		return $this->user() !== null;
	}

	/**
	 * Call ONLY after a successful attempt()/loginFromCookie().
	 * Returns the 'selector:validator' cookie value - controller sets the cookie.
	 */
	public function issueRememberToken(): ?string
	{
		$user = $this->user();

		if($user === null){ return null; }

		$this->tokens->purgeExpired();

		$selector = bin2hex(random_bytes(16)); // 32 hex chars - the lookup key
		$validator = bin2hex(random_bytes(32)); // 64 hex chars - the secret

		$days = (int) $this->config->get('auth.remember_lifetime_days', 30);

		$this->tokens->create(
			userId: $user->id,
			selector: $selector,
			validatorHash: hash('sha256', $validator), // raw validator NEVER stored
			expiresAt: date('Y-m-d H:i:s', time() + $days * 86400)
		);

		return $selector.':'.$validator;
	}

	/**
	 * Login from the remember cookie. Returns the ROTATED cookie value,
	 * or null = cookie invalid and the caller must clear it.
	 */
	public function loginFromCookie(string $cookieValue): ?string
	{
		$parts = explode(':', $cookieValue);

		if(count($parts) !== 2){ return null; }

		[$selector, $validator] = $parts;

		$token = $this->tokens->findBySelector($selector);

		if($token === null){ return null; }

		if($token->expiresAt <= new DateTimeImmutable()){
			$this->tokens->deleteBySelector($selector);
			return null;
		}

		// constant-time compare - project rule for every token check
		if(!hash_equals($token->validatorHash, hash('sha256', $validator))){
			// selector right + validator wrong = this cookie was stolen and the
			// thief already rotated it. Kill EVERY token, force re-login everywhere
			$this->tokens->deleteForUser($token->userId);
			return null;
		}

		$user = $this->users->findById($token->userId);

		if($user === null || !$user->is_active){
			$this->tokens->deleteForUser($token->userId);
			return null;
		}

		// single-use: every successful cookie login burns the token
		$this->tokens->deleteBySelector($selector);

		$this->session->regenerate();
		$this->session->set(self::SESSION_KEY, $user->id);
		$this->user = $user;

		return $this->issueRememberToken();
	}

	/** $cookieValue = current remember cookie, so its DB row dies with the session */
	public function logout(?string $cookieValue): void
	{
		if($cookieValue !== null){
			$selector = explode(':', $cookieValue)[0];

			if($selector !== ''){
				$this->tokens->deleteBySelector($selector);
			}
		}

		$this->user = null;
		$this->session->invalidate();
	}
}