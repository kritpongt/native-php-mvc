<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\RoleRepo;
use App\Models\UserRepo;
use App\Models\User;
use InvalidArgumentException;

final class UserService
{
	public function __construct(
		private readonly UserRepo $users,
		private readonly RoleRepo $roles,
		private readonly AuditLogger $auditLogger,
	){}

	public function create(string $name, string $email, string $password, bool $isActive, ?string $roleName): User
	{
		$role = null;
		if($roleName !== null){
			$role = $this->roles->findByName($roleName);

			if($role === null){
				throw new InvalidArgumentException("role not found: {$roleName}");
			}
		}

		$user = $this->users->create(
			email: $email,
			passwordHash: password_hash($password, PASSWORD_ARGON2ID),
			name: $name,
			isActive: $isActive
		);

		if($role !== null){
			$this->roles->assignToUser($user->id, $role->id);
		}

		$this->auditLogger->log(
			action: 'CREATE',
			tableName: 'users',
			recordId: $user->id,
			newValues: ['name' => $name, 'email' => $email, 'is_active' => $isActive, 'role' => $roleName]
		);

		return $user;
	}

	public function update(int $id, string $name, string $email, bool $isActive, ?string $roleName, ?string $password = null): void
	{
		$roleName = ($roleName !== null) ? trim($roleName) : null;
		$roleName = ($roleName === '') ? null : $roleName;

		$role = null;
		if($roleName !== null){
			$role = $this->roles->findByName($roleName);
			if($role === null){
				throw new InvalidArgumentException("role not found: {$roleName}");
			}
		}

		$oldUser = $this->users->findByIdWithRoles($id);
		if ($oldUser === null) {
			throw new InvalidArgumentException("user not found with ID: {$id}");
		}

		$oldRoleName = (!empty($oldUser->roles)) ? $oldUser->roles[0] : null;
		$oldData = [
			'name' => $oldUser->name,
			'email' => $oldUser->email,
			'is_active' => $oldUser->is_active,
			'role' => $oldRoleName
		];

		$passwordHash = null;
		$isPasswordChanged = false;
		if ($password !== null && trim($password) !== '') {
			$passwordHash = password_hash($password, PASSWORD_ARGON2ID);
			$isPasswordChanged = true;
		}

		$newData = [
			'name' => $name,
			'email' => $email,
			'is_active' => $isActive,
			'role' => $roleName
		];

		// Structural Equality
		if(!$isPasswordChanged && $oldData === $newData){ return; }

		$this->users->update($id, $email, $name, $isActive, $passwordHash);

		if($roleName !== $oldRoleName){
			$this->roles->revokeAllFromUser($id);
			if($role !== null){
				$this->roles->assignToUser($id, $role->id);
			}
		}

		$this->auditLogger->log(
			action: 'UPDATE',
			tableName: 'users',
			recordId: $id,
			oldValues: $oldData,
			newValues: $newData
		);
	}

	public function delete(int $id, int $actingUserId): void
	{
		if($id === $actingUserId){
			throw new InvalidArgumentException('cannot delete your own account');
		}

		$oldUser = $this->users->findByIdWithRoles($id);
		if($oldUser === null){
			throw new InvalidArgumentException("user not found with ID: {$id}");
		}

		$oldRoleName = (!empty($oldUser->roles)) ? $oldUser->roles[0] : null;
		$oldData = [
			'name' => $oldUser->name,
			'email' => $oldUser->email,
			'is_active' => $oldUser->is_active,
			'role' => $oldRoleName
		];

		$this->users->delete($id);

		$this->auditLogger->log(
			action: 'DELETE',
			tableName: 'users',
			recordId: $id,
			oldValues: $oldData
		);
	}
}