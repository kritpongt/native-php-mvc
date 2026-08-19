<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLogRepo;
use Core\ClientContextInterface;
use Core\Request;

class AuditLogger
{
	public function __construct(
		private readonly AuditLogRepo $auditLogRepo,
		private readonly AuthService $auth,
		private readonly ClientContextInterface $context
	){}

	public function log(
		string $action,
		string $tableName,
		int $recordId,
		?array $oldValues = null,
		?array $newValues = null
	): void {
		$userId = $this->auth->user()?->id;
		$ipAddress = $this->context->ip();

		$this->auditLogRepo->create(
			userId: $userId,
			action: $action,
			tableName: $tableName,
			recordId: $recordId,
			oldValues: $oldValues,
			newValues: $newValues,
			ipAddress: $ipAddress
		);
	}
}