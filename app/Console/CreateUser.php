<?php
declare(strict_types=1);

namespace App\Console;

use App\Models\RoleRepo;
use App\Models\UserRepo;
use Core\Console\Input;
use Core\Console\Prompt;

/**
 * creates one admin user - the ONLY way in, backoffice has no public register
 * How to use:
 *   php bin/console user:create --email=user@email.com --name="Alex" --role=admin
 */
final class CreateUser
{
	public function __construct(
		private readonly UserRepo $users,
		private readonly RoleRepo $roles
	){}

	/** @param list<string> $argv the raw bin/console args */
	public function run(array $argv): int
	{
		$input = new Input($argv);
		$email = $input->getOption('--email') ?? Prompt::ask('Email: ');
		$email = strtolower(trim($email));

		if(filter_var($email, FILTER_VALIDATE_EMAIL) === false){
			fwrite(STDERR, "Invalid email.\n");
			return 1;
		}

		if($this->users->findByEmail($email) !== null){
			fwrite(STDERR, "Email already exists: {$email}\n");
			return 1;
		}

		$name = $input->getOption('--name') ?? Prompt::ask('Name: ');
		$name = trim($name);

		if($name === ''){
			fwrite(STDERR, "Name cannot be empty.\n");
			return 1;
		}

		$password = Prompt::secret('Password: ');
		$confirm = Prompt::secret('Confirm password: ');

		// if(strlen($password) < 8){
		// 	fwrite(STDERR, "Password must be at least 8 characters.\n");
		// 	return 1;
		// }

		if(!hash_equals($password, $confirm)){
			fwrite(STDERR, "Passwords do not match.\n");
			return 1;
		}

		// project rule: Argon2id only
		$hash = password_hash($password, PASSWORD_ARGON2ID);

		$user = $this->users->create($email, $hash, $name);

		// optional --role=admin assigns straight away (role must exist - run rbac:sync first)
		$roleName = $input->getOption('--role');

		if($roleName !== null){
			$role = $this->roles->findByName($roleName);

			if($role === null){
				fwrite(STDERR, "User created (id {$user->id}) but role '{$roleName}' not found. Run rbac:sync.\n");
				return 1;
			}

			$this->roles->assignToUser($user->id, $role->id);
			echo "Created user {$user->id} ({$email}) with role '{$roleName}'.\n";
			return 0;
		}

		echo "Created user {$user->id} ({$email}). No role assigned.\n";
		return 0;
	}

	/** read --key=value or --key value from argv */
	// private function argValue(array $argv, string $flag): ?string
	// {
	// 	foreach($argv as $i => $arg){
	// 		if($arg === $flag){
	// 			return $argv[$i + 1] ?? null;
	// 		}
	// 		if(str_starts_with($arg, $flag.'=')){
	// 			return substr($arg, strlen($flag) + 1);
	// 		}
	// 	}

	// 	return null;
	// }
}