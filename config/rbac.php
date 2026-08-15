<?php
declare(strict_types=1);

// source of truth for RBAC - php bin/console rbac:sync upserts this into the DB.
// code checks permissions by these exact strings: $authz->can($user, 'users.manage')
return [
	'permissions' => [
		'users.view',
		'users.add',
		'users.edit',
		'users.delete',
		'users.manage',
		'roles.view',
		'roles.add',
		'roles.edit',
		'roles.delete',
		'roles.manage'
	],
	// role => list of permissions. '*' = everything in 'permissions' above
	'roles' => [
		'admin' => ['*']
	]
];