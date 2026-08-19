<?php
declare(strict_types=1);

namespace App\Models;

final class AuditLog
{
	public function __construct(
		public readonly int $id,
		public readonly ?int $user_id,
		public readonly string $action,
		public readonly string $table_name,
		public readonly int $record_id,
		public readonly ?string $old_values,
		public readonly ?string $new_values,
		public readonly ?string $ip_address,
		public readonly string $created_at
	){}
}
