<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\RoleRepo;
use App\Models\UserRepo;

final class UserService
{
	public function __construct(
		private readonly UserRepo $users,
		private readonly RoleRepo $roles,
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

		return $user;
	}
}