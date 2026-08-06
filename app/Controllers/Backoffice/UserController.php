<?php
declare(strict_types=1);
 
namespace App\Controllers\Backoffice;

use App\Models\UserRepo;
use App\Services\AuthService;
use Core\Request;
use Core\Response;
use Core\View;

final class UserController
{
	public function __construct(
		private readonly AuthService $auth,
		private readonly UserRepo $userRepo,
		private readonly View $view
	){}

	public function index(Request $request): Response
	{
		$page = (int) ($request->input('page') ?? 1);
		$filters = [
			'search' => (string) ($request->input('q') ?? ''),
			'role' => (string) ($request->input('role') ?? ''),
			'isActive' => (string) ($request->input('is_active') ?? '')
		];

		$result = $this->userRepo->paginate($filters, $page);
		$data = [
			'users' => $result['items'],
			'total' => $result['total'],
			'currentPage' => $page,
			'search' => $filters['search'],
			'role' => $filters['role'],
			'isActive' => $filters['isActive'],
			'user' => $this->auth->user()
		];

		if($request->isHtmx()){
			return Response::html($this->view->renderPartial('backoffice/users', $data));
		}

		return Response::html($this->view->renderPage('backoffice/users', $data));
	}
}