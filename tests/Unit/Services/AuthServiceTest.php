<?php
declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\LoginAttemptRepo;
use App\Models\RememberToken;
use App\Models\RememberTokenRepo;
use App\Models\User;
use App\Models\UserRepo;
use App\Services\AuthService;
use App\Services\LoginResult;
use Core\Config;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeSession;

final class AuthServiceTest extends TestCase
{
	private UserRepo $users;
	private LoginAttemptRepo $attempts;
	private RememberTokenRepo $tokens;
	private FakeSession $session;
	private Config $config;

	protected function setUp(): void
	{
		// repos are non-final -> PHPUnit can mock them directly (project rule)
		$this->users = $this->createMock(UserRepo::class);
		$this->attempts = $this->createMock(LoginAttemptRepo::class);
		$this->tokens = $this->createMock(RememberTokenRepo::class);
		$this->session = new FakeSession();
		$this->config = new Config(__DIR__.'/../../Fixtures/config');
	}

	private function service(): AuthService
	{
		return new AuthService($this->users, $this->attempts, $this->tokens, $this->session, $this->config);
	}

	private function activeUser(string $password): User
	{
		return new User(1, 'a@b.com', password_hash($password, PASSWORD_ARGON2ID), 'Admin', true);
	}

	public function test_rate_limited_before_any_password_verify(): void
	{
		$this->attempts->method('countSince')->willReturn(5); // at the limit

		// the whole point: locked out attacker never reaches argon2id, no CPU burned
		$this->users->expects($this->never())->method('findByEmail');

		$this->assertSame(LoginResult::RateLimited, $this->service()->attempt('a@b.com', 'x', '1.1.1.1'));
	}

	public function test_unknown_email_fails_and_records_attempt(): void
	{
		$this->attempts->method('countSince')->willReturn(0);
		$this->users->method('findByEmail')->willReturn(null); // no such user
		$this->attempts->expects($this->once())->method('record');

		$result = $this->service()->attempt('nope@b.com', 'x', '1.1.1.1');

		$this->assertSame(LoginResult::Failed, $result);
		$this->assertSame(0, $this->session->regenerateCount); // no login happened
	}

	public function test_wrong_password_fails(): void
	{
		$this->attempts->method('countSince')->willReturn(0);
		$this->users->method('findByEmail')->willReturn($this->activeUser('correct'));
		$this->attempts->expects($this->once())->method('record');

		$this->assertSame(LoginResult::Failed, $this->service()->attempt('a@b.com', 'wrong', '1.1.1.1'));
	}

	public function test_inactive_user_fails_even_with_right_password(): void
	{
		$this->attempts->method('countSince')->willReturn(0);
		$inactive = new User(1, 'a@b.com', password_hash('correct', PASSWORD_ARGON2ID), 'Admin', false);
		$this->users->method('findByEmail')->willReturn($inactive);

		$this->assertSame(LoginResult::Failed, $this->service()->attempt('a@b.com', 'correct', '1.1.1.1'));
	}

	public function test_success_regenerates_session_and_clears_attempts(): void
	{
		$this->attempts->method('countSince')->willReturn(0);
		$this->users->method('findByEmail')->willReturn($this->activeUser('correct'));
		$this->attempts->expects($this->once())->method('clear');

		$result = $this->service()->attempt('a@b.com', 'correct', '1.1.1.1');

		$this->assertSame(LoginResult::Success, $result);
		$this->assertSame(1, $this->session->regenerateCount); // fixation defense
		$this->assertSame(1, $this->session->get('auth_user_id'));
	}

	public function test_issue_remember_token_stores_hash_never_raw(): void
	{
		$this->attempts->method('countSince')->willReturn(0);
		$this->users->method('findByEmail')->willReturn($this->activeUser('correct'));

		$svc = $this->service();
		$svc->attempt('a@b.com', 'correct', '1.1.1.1'); // sets the per-request user

		$captured = [];
		$this->tokens->expects($this->once())->method('create')
			->willReturnCallback(function(int $userId, string $selector, string $validatorHash, string $expiresAt) use (&$captured): void{
				$captured = ['selector' => $selector, 'validatorHash' => $validatorHash];
			});

		$cookie = $svc->issueRememberToken();

		$this->assertNotNull($cookie);
		[$selector, $validator] = explode(':', $cookie);

		// DB must hold sha256(validator), the raw validator lives ONLY in the cookie
		$this->assertSame($selector, $captured['selector']);
		$this->assertSame(hash('sha256', $validator), $captured['validatorHash']);
		$this->assertNotSame($validator, $captured['validatorHash']);
	}

	public function test_cookie_login_valid_rotates_token(): void
	{
		$token = new RememberToken(10, 1, 'sel', hash('sha256', 'val'), new DateTimeImmutable('+1 day'));
		$this->tokens->method('findBySelector')->willReturn($token);
		$this->users->method('findById')->willReturn($this->activeUser('correct'));

		$this->tokens->expects($this->once())->method('deleteBySelector')->with('sel'); // single-use burn
		$this->tokens->expects($this->once())->method('create'); // fresh token issued

		$rotated = $this->service()->loginFromCookie('sel:val');

		$this->assertNotNull($rotated);
		$this->assertSame(1, $this->session->regenerateCount);
		$this->assertSame(1, $this->session->get('auth_user_id'));
	}

	public function test_cookie_login_forged_validator_purges_all_tokens(): void
	{
		$token = new RememberToken(10, 1, 'sel', hash('sha256', 'real'), new DateTimeImmutable('+1 day'));
		$this->tokens->method('findBySelector')->willReturn($token);

		// selector right + validator wrong = theft -> nuke every device
		$this->tokens->expects($this->once())->method('deleteForUser')->with(1);

		$this->assertNull($this->service()->loginFromCookie('sel:forged'));
		$this->assertSame(0, $this->session->regenerateCount);
	}

	public function test_cookie_login_expired_deletes_and_fails(): void
	{
		$token = new RememberToken(10, 1, 'sel', hash('sha256', 'val'), new DateTimeImmutable('-1 day'));
		$this->tokens->method('findBySelector')->willReturn($token);
		$this->tokens->expects($this->once())->method('deleteBySelector')->with('sel');

		$this->assertNull($this->service()->loginFromCookie('sel:val'));
	}

	public function test_cookie_login_malformed_value_returns_null(): void
	{
		$this->tokens->expects($this->never())->method('findBySelector');

		$this->assertNull($this->service()->loginFromCookie('no-colon-here'));
	}

	public function test_logout_invalidates_session_and_burns_token(): void
	{
		$this->tokens->expects($this->once())->method('deleteBySelector')->with('sel');

		$this->service()->logout('sel:whatever');

		$this->assertSame(1, $this->session->invalidateCount);
	}
}