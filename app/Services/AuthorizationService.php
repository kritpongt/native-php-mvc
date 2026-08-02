<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\PermissionRepo;
use App\Models\User;

final class AuthorizationService
{
	/**
	 * Per-request cache: ONE join query per user, never stored in session -
	 * permission changes in the DB take effect on the next request.
	 * 
	 * @var array<int, list<string>>
	 */
	private array $cache = [];

	public function __construct(private readonly PermissionRepo $permissions){}

	public function can(User $user, string $permissions): bool
	{
		$this->cache[$user->id] ??= $this->permissions->namesForUser($user->id);

		return in_array($permissions, $this->cache[$user->id], true);
	}
}