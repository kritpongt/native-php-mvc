<?php
declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\PermissionRepo;
use App\Models\User;
use App\Services\AuthorizationService;
use PHPUnit\Framework\TestCase;

final class AuthorizationServiceTest extends TestCase
{
	private function user(): User
	{
		return new User(7, 'a@b.com', 'hash', 'Admin', true);
	}

	public function test_can_returns_true_when_permission_granted(): void
	{
		$repo = $this->createMock(PermissionRepo::class);
		$repo->method('namesForUser')->willReturn(['users.view', 'users.manage']);

		$authz = new AuthorizationService($repo);

		$this->assertTrue($authz->can($this->user(), 'users.manage'));
	}

	public function test_can_returns_false_when_permission_missing(): void
	{
		$repo = $this->createMock(PermissionRepo::class);
		$repo->method('namesForUser')->willReturn(['users.view']);

		$authz = new AuthorizationService($repo);

		$this->assertFalse($authz->can($this->user(), 'users.manage'));
	}

	public function test_permissions_queried_once_per_user_then_cached(): void
	{
		$repo = $this->createMock(PermissionRepo::class);

		// per-request cache: many can() checks, ONE join query
		$repo->expects($this->once())->method('namesForUser')->willReturn(['users.view']);

		$authz = new AuthorizationService($repo);
		$authz->can($this->user(), 'users.view');
		$authz->can($this->user(), 'users.manage');
		$authz->can($this->user(), 'users.view');
	}
}