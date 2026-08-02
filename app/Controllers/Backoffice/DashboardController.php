<?php
declare(strict_types=1);

namespace App\Controllers\Backoffice;

use App\Services\AuthService;
use Core\Request;
use Core\Response;
use Core\View;

final class DashboardController
{
	public function __construct(
		private readonly AuthService $auth,
		private readonly View $view
	){}

	public function index(Request $request): Response
	{
		// Auth middleware guarantees a user here
		return Response::html($this->view->renderPage('backoffice/dashboard', [
			'user' => $this->auth->user()
		]));
	}
}