<?php
declare(strict_types=1);

namespace App\Console;

use App\Models\RoleRepo;
use App\Models\UserRepo;
use Core\Console\Input;
use Core\Console\Prompt;

/**
 * Assign role an exist user
 * How to use:
 * 	php bin/console user:assign --email=user@email.com --role=admin
 */
final class AssignRole
{
	public function __construct(
		private readonly UserRepo $users,
		private readonly RoleRepo $roles
	){}

	/** @param list<string> $argv */
	public function run(array $argv): int
	{
		$input = new Input($argv);
		$email = $input->getOption('--email') ?? Prompt::ask('User Email: ');
		$email = strtolower(trim($email));

		$user = $this->users->findByEmail($email);
		if($user === null){
			fwrite(STDERR, "User with email '{$email}' not found.\n");
			return 1;
		}

		$roleName = $input->getOption('--role') ?? Prompt::ask('Role Name: ');
		$roleName = trim($roleName);

		$role = $this->roles->findByName($roleName);
		if($role === null){
			fwrite(STDERR, "Role '{$roleName}' not found. Please sync roles first.\n");
			return 1;
		}

		$this->roles->assignToUser($user->id, $role->id);
		echo "Successfully assigned role '{$roleName}' to user {$user->id} ({$email}).\n";
		return 0;
	}
}