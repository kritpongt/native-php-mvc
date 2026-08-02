<?php
declare(strict_types=1);

namespace App\Console;

use App\Models\PermissionRepo;
use App\Models\RoleRepo;

// config/rbac.php is the source of truth - this upserts it into the DB.
// idempotent: existing rows are reused, pivots rebuilt to match config exactly
final class SyncRbac
{
	public function __construct(
		private readonly RoleRepo $roles,
		private readonly PermissionRepo $permissions,
		/** @var array{permissions: list<string>, roles: array<string, list<string>>} */
		private readonly array $rbac
	){}

	public function run(): int
	{
		// 1. upsert every permission, keep name => id map
		$permIds = [];

		foreach($this->rbac['permissions'] as $name){
			$perm = $this->permissions->findByName($name) ?? $this->permissions->create($name);
			$permIds[$name] = $perm->id;
			echo "permission: {$name}\n";
		}

		// 2. upsert each role, then rebuild its pivot rows from config
		foreach($this->rbac['roles'] as $roleName => $grants){
			$role = $this->roles->findByName($roleName) ?? $this->roles->create($roleName);

			// '*' means every permission that exists in config
			$wanted = in_array('*', $grants, true)
				? array_keys($permIds)
				: $grants;

			// wipe then re-attach = DB ends up EXACTLY matching config, no stale grants
			$this->permissions->detachAllFromRole($role->id);

			foreach($wanted as $permName){
				if(!isset($permIds[$permName])){
					fwrite(STDERR, "role '{$roleName}' wants unknown permission '{$permName}' - skipped\n");
					continue;
				}

				$this->permissions->attachToRole($role->id, $permIds[$permName]);
			}

			echo "role: {$roleName} -> ".implode(', ', $wanted)."\n";
		}

		echo "RBAC sync complete.\n";

		return 0;
	}
}