<?php
declare(strict_types=1);
 
namespace App\Controllers\Backoffice;

use App\Models\AuditLogRepo;
use App\Services\AuthService;
use Core\Request;
use Core\Response;
use Core\View;

final class AuditLogController
{
	public function __construct(
		private readonly AuthService $auth,
		private readonly AuditLogRepo $auditLogRepo,
		private readonly View $view
	){}

	public function index(Request $request): Response
	{
		$page = (int) ($request->query('page') ?? 1);
		$filters = [
			'search' => (string) ($request->query('q') ?? ''),
			'action' => (string) ($request->query('action') ?? ''),
			'table_name' => (string) ($request->query('table_name') ?? '')
		];

		$result = $this->auditLogRepo->paginate($filters, $page);
		
		// To populate filter dropdowns, we could fetch distinct actions and table names,
		// but for simplicity we can just hardcode or leave as input fields in the view.
		// For now we'll pass standard actions and tables, or let the view handle it.

		$data = [
			'logs' => $result['items'],
			'total' => $result['total'],
			'currentPage' => $page,
			'search' => $filters['search'],
			'action' => $filters['action'],
			'table_name' => $filters['table_name'],
			'user' => $this->auth->user()
		];

		if($request->isHtmx()){
			return Response::html($this->view->renderPartial('backoffice/audit/index', $data));
		}

		return Response::html($this->view->renderPage('backoffice/audit/index', $data));
	}
}
