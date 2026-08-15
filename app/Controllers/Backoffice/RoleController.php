<?php
declare(strict_types=1);
 
namespace App\Controllers\Backoffice;

use App\Models\PermissionRepo;
use App\Models\RoleRepo;
use App\Services\AuthService;
use Core\Request;
use Core\Response;
use Core\SessionInterface;
use Core\View;

final class RoleController
{
	public function __construct(
		private readonly AuthService $auth,
		private readonly RoleRepo $roleRepo,
		private readonly PermissionRepo $permissionRepo,
		private readonly SessionInterface $session,
		private readonly View $view
	){}

	public function index(Request $request): Response
	{
		$data = [
			'roles' => $this->roleRepo->all(),
			'success' => $this->session->getFlash('roles_success'),
			'error' => $this->session->getFlash('roles_error'),
			'user' => $this->auth->user()
		];

		if($request->isHtmx()){
			return Response::html($this->view->renderPartial('backoffice/roles/index', $data));
		}

		return Response::html($this->view->renderPage('backoffice/roles/index', $data));
	}

	public function create(Request $request): Response
	{
		$data = [
			'permissions' => $this->groupPermissions($this->permissionRepo->all()),
			'old' => $this->session->getFlash('roles_create_old', []),
			'errors' => $this->session->getFlash('roles_create_errors', []),
			'user' => $this->auth->user()
		];

		if($request->isHtmx()){
			return Response::html($this->view->renderPartial('backoffice/roles/create', $data));
		}

		return Response::html($this->view->renderPage('backoffice/roles/create', $data));
	}

	public function store(Request $request): Response
	{
		$name = trim((string) $request->input('name', ''));
		$permissions = (array) $request->input('permissions', []);

		$errors = [];

		if($name === ''){
			$errors['name'] = 'backoffice.roles.error_name_required';
		}
		if($this->roleRepo->findByName($name) !== null){
			$errors['name'] = 'backoffice.roles.error_name_taken';
		}

		if($errors !== []){
			$this->session->flash('roles_create_errors', $errors);
			$this->session->flash('roles_create_old', ['name' => $name, 'permissions' => $permissions]);
			
			return $request->isHtmx()
				? (new Response('', 204))->withHeader('HX-Location', '{"path": "/backoffice/roles/create", "target": "#main-content"}')
				: Response::redirect('/backoffice/roles/create');
		}

		$role = $this->roleRepo->create($name);
		foreach($permissions as $permId){
			$this->permissionRepo->attachToRole($role->id, (int) $permId);
		}

		$this->session->flash('roles_success', 'backoffice.roles.success_created');

		return $request->isHtmx()
			? (new Response('', 204))->withHeader('HX-Location', '{"path": "/backoffice/roles", "target": "#main-content"}')
			: Response::redirect('/backoffice/roles');
	}

	public function edit(Request $request, string $id): Response
	{
		$roleId = ctype_digit($id) ? (int) $id : 0;
		$targetRole = $this->roleRepo->findById($roleId);

		if ($targetRole === null) {
			$this->session->flash('roles_error', 'backoffice.roles.error_not_found');
			return $this->redirectToRoles($request);
		}

		$data = [
			'targetRole' => $targetRole,
			'permissions' => $this->groupPermissions($this->permissionRepo->all()),
			'rolePermissions' => $this->permissionRepo->getIdsForRole($roleId),
			'errors' => $this->session->getFlash('roles_edit_errors', []),
			'user' => $this->auth->user()
		];

		if ($request->isHtmx()) {
			return Response::html($this->view->renderPartial("backoffice/roles/edit", $data));
		}

		return Response::html($this->view->renderPage("backoffice/roles/edit", $data));
	}

	public function update(Request $request, string $id): Response
	{
		$roleId = ctype_digit($id) ? (int) $id : 0;
		$name = trim((string) $request->input('name', ''));
		$permissions = (array) $request->input('permissions', []);

		$errors = [];

		if($name === ''){
			$errors['name'] = 'backoffice.roles.error_name_required';
		}
		if($this->roleRepo->findByNameExceptId($name, $roleId) !== null){
			$errors['name'] = 'backoffice.roles.error_name_taken';
		}

		if($errors !== []){
			$this->session->flash('roles_edit_errors', $errors);
			
			return $request->isHtmx()
				? (new Response('', 204))->withHeader('HX-Location', '{"path": "/backoffice/roles/'.$roleId.'/edit", "target": "#main-content"}')
				: Response::redirect("/backoffice/roles/{$roleId}/edit");
		}

		$this->roleRepo->update($roleId, $name);
		
		$this->permissionRepo->detachAllFromRole($roleId);
		foreach($permissions as $permId){
			$this->permissionRepo->attachToRole($roleId, (int) $permId);
		}

		$this->session->flash('roles_success', 'backoffice.roles.success_edited');
	
		return $request->isHtmx()
			? (new Response('', 204))->withHeader('HX-Location', '{"path": "/backoffice/roles", "target": "#main-content"}')
			: Response::redirect('/backoffice/roles');
	}

	public function destroy(Request $request, string $id): Response
	{
		$roleId = ctype_digit($id) ? (int) $id : 0;

		if($roleId === 0 || $this->roleRepo->findById($roleId) === null){
			$this->session->flash('roles_error', 'backoffice.roles.error_not_found');
			return $this->redirectToRoles($request);
		}

		$this->roleRepo->delete($roleId);

		$this->session->flash('roles_success', 'backoffice.roles.success_deleted');

		return $this->redirectToRoles($request);
	}

	private function redirectToRoles(Request $request): Response
	{
		$location['path'] = '/backoffice/roles';
		if ($targetId = $request->header('HX-Target')) {
			$location['target'] = '#'.$targetId;
		}

		$hxLocation = json_encode($location, JSON_UNESCAPED_SLASHES);

		return $request->isHtmx()
			? (new Response('', 204))->withHeader('HX-Location', $hxLocation)
			: Response::redirect('/backoffice/roles');
	}

	private function groupPermissions(array $permissions): array
	{
		$grouped = [];
		foreach ($permissions as $perm) {
			$parts = explode('.', $perm->name, 2);
			$category = $parts[0] ?? 'general';
			$grouped[$category][] = $perm;
		}
		return $grouped;
	}
}