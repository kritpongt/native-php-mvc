<?php
declare(strict_types=1);
 
namespace App\Controllers\Backoffice;

use App\Models\RoleRepo;
use App\Models\UserRepo;
use App\Services\AuthService;
use App\Services\UserService;
use Core\Request;
use Core\Response;
use Core\SessionInterface;
use Core\View;
use InvalidArgumentException;

final class UserController
{
	public function __construct(
		private readonly AuthService $auth,
		private readonly UserService $user,
		private readonly UserRepo $userRepo,
		private readonly RoleRepo $roleRepo,
		private readonly SessionInterface $session,
		private readonly View $view
	){}

	public function index(Request $request): Response
	{
		$page = (int) ($request->query('page') ?? 1);
		$filters = [
			'search' => (string) ($request->query('q') ?? ''),
			'role' => (string) ($request->query('role') ?? ''),
			'isActive' => (string) ($request->query('is_active') ?? '')
		];

		$result = $this->userRepo->paginate($filters, $page);
		$data = [
			'users' => $result['items'],
			'total' => $result['total'],
			'currentPage' => $page,
			'search' => $filters['search'],
			'role' => $filters['role'],
			'isActive' => $filters['isActive'],
			'success' => $this->session->getFlash('users_success'),
			'error' => $this->session->getFlash('users_error'),
			'user' => $this->auth->user()
		];

		if($request->isHtmx()){
			return Response::html($this->view->renderPartial('backoffice/users/index', $data));
		}

		return Response::html($this->view->renderPage('backoffice/users/index', $data));
	}

	/** GET /backoffice/users/create */
	public function create(Request $request): Response
	{
		$data = [
			'roles' => $this->roleRepo->all(),
			'old' => $this->session->getFlash('users_create_old', []),
			'errors' => $this->session->getFlash('users_create_errors', []),
			'user' => $this->auth->user()
		];

		if($request->isHtmx()){
			return Response::html($this->view->renderPartial('backoffice/users/create', $data));
		}

		return Response::html($this->view->renderPage('backoffice/users/create', $data));
	}

	/** POST /backoffice/users */
	public function store(Request $request): Response
	{
		$email = strtolower(trim((string) $request->input('email', '')));
		$password = (string) $request->input('password', '');
		$confirmPassword = (string) $request->input('confirm_password', '');
		$name = trim((string) $request->input('name', ''));
		$role = trim((string) $request->input('role', ''));
		$isActive = $request->input('is_active') === '1';

		$errors = [];

		if($name === ''){
			$errors['name'] = 'backoffice.users.error_name_required';
		}
		if(filter_var($email, FILTER_VALIDATE_EMAIL) === false){
			$errors['email'] = 'backoffice.users.error_email_invalid';
		}
		if($this->userRepo->findByEmail($email) !== null){
			$errors['email'] = 'backoffice.users.error_email_taken';
		}
		if(strlen($password) < 8){
			$errors['password'] = 'backoffice.users.error_password_min';
		}
		if(!hash_equals($password, $confirmPassword)){
			$errors['confirm_password'] = 'backoffice.users.error_password_mismatch';
		}
		if($role !== '' && $this->roleRepo->findByName($role) === null){
			$errors['role'] = 'backoffice.users.error_role_invalid';
		}

		$old = ['name' => $name, 'email' => $email, 'role' => $role, 'is_active' => $isActive];

		if($errors !== []){
			return $this->backToCreate($request, $errors, $old);
		}

		$this->user->create($name, $email, $password, $isActive, $role === '' ? null : $role);

		$this->session->flash('users_success', 'backoffice.users.success_created');

		// return Response::redirect('/bakcoffice/users');
		return $request->isHtmx()
			? (new Response('', 204))->withHeader('HX-Location', '{"path": "/backoffice/users", "target": "#main-content"}')
			: Response::redirect('/backoffice/users');
	}

	private function backToCreate(Request $request, array $errors, array $old): Response
	{
		$this->session->flash('users_create_errors', $errors);
		$this->session->flash('users_create_old', $old);
		
		// return Response::redirect('/bakcoffice/users/create');
		return $request->isHtmx()
			? (new Response('', 204))->withHeader('HX-Location', '{"path": "/backoffice/users/create", "target": "#main-content"}')
			: Response::redirect('/backoffice/users/create');
	}

	public function edit(Request $request, string $id): Response
	{
		$userId = ctype_digit($id) ? (int) $id : 0;
		$targetUser = $this->userRepo->findById($userId);

		if ($targetUser === null) {
			$this->session->flash('users_error', 'backoffice.users.error_not_found');
			return $this->redirectToUsers($request);
		}

		$data = [
			'targetUser' => $targetUser,
			'roles' => $this->roleRepo->all(),
			'errors' => $this->session->getFlash('users_edit_errors', []),
			'user' => $this->auth->user()
		];

		if ($request->isHtmx()) {
			return Response::html($this->view->renderPartial("backoffice/users/edit", $data));
		}

		return Response::html($this->view->renderPage("backoffice/users/edit", $data));
	}

	public function update(Request $request, string $id): Response
	{
		$userId = ctype_digit($id) ? (int) $id : 0;
		
		$email = strtolower(trim((string) $request->input('email', '')));
		$password = (string) $request->input('password', '');
		$confirmPassword = (string) $request->input('confirm_password', '');
		$name = trim((string) $request->input('name', ''));
		$role = trim((string) $request->input('role', ''));
		$isActive = $request->input('is_active') === '1';

		$errors = [];

		if($name === ''){
			$errors['name'] = 'backoffice.users.error_name_required';
		}
		if(filter_var($email, FILTER_VALIDATE_EMAIL) === false){
			$errors['email'] = 'backoffice.users.error_email_invalid';
		}
		if($this->userRepo->findByEmailExceptId($email, $userId) !== null){
			$errors['email'] = 'backoffice.users.error_email_taken';
		}
		if($password !== '' && strlen($password) < 8){
			$errors['password'] = 'backoffice.users.error_password_min';
		}
		if($password !== '' && !hash_equals($password, $confirmPassword)){
			$errors['confirm_password'] = 'backoffice.users.error_password_mismatch';
		}
		if($role !== '' && $this->roleRepo->findByName($role) === null){
			$errors['role'] = 'backoffice.users.error_role_invalid';
		}

		if($errors !== []){
			return $this->backToEdit($request, $errors, $userId);
		}

		$this->user->update($userId, $name, $email, $isActive, $role, $password);

		$this->session->flash('users_success', 'backoffice.users.success_edited');
	
		return $request->isHtmx()
			? (new Response('', 204))->withHeader('HX-Location', '{"path": "/backoffice/users", "target": "#main-content"}')
			: Response::redirect('/backoffice/users');
	}

	private function backToEdit(Request $request, array $errors, int $userId): Response
	{
		$this->session->flash('users_edit_errors', $errors);
		
		return $request->isHtmx()
			? (new Response('', 204))->withHeader('HX-Location', '{"path": "/backoffice/users/'.$userId.'/edit", "target": "#main-content"}')
			: Response::redirect("/backoffice/users/{$userId}/edit");
	}

	public function destroy(Request $request, string $id): Response
	{
		$currentUser = $this->auth->user();

    // Auth middleware should have caught this - belt and suspenders
    if($currentUser === null){
			return $this->redirectToUsers($request);
    }

		// URL params arrive as strings - reject junk before casting
    $id = ctype_digit($id) ? (int) $id : 0;

    if($id === 0 || $this->userRepo->findById($id) === null){
			$this->session->flash('users_error', 'backoffice.users.error_not_found');

			return $this->redirectToUsers($request);
    }

    try{
			$this->user->delete($id, $currentUser->id);
    }catch(InvalidArgumentException){
			$this->session->flash('users_error', 'backoffice.users.error_cannot_delete_self');

			return $this->redirectToUsers($request);
    }

    $this->session->flash('users_success', 'backoffice.users.success_deleted');

    return $this->redirectToUsers($request);
	}

	/** POST feedback - Post/Redirect/Get */
	private function redirectToUsers(Request $request): Response
	{
		$location['path'] = '/backoffice/users';
		if ($targetId = $request->header('HX-Target')) {
			$location['target'] = '#'.$targetId;
		}

		$hxLocation = json_encode($location, JSON_UNESCAPED_SLASHES);

		return $request->isHtmx()
			? (new Response('', 204))->withHeader('HX-Location', $hxLocation)
			: Response::redirect('/backoffice/users');
	}
}