<?php
declare(strict_types=1);

namespace App\Controllers\Backoffice;

use App\Services\AuthService;
use App\Services\LoginResult;
use Core\Config;
use Core\Request;
use Core\Response;
use Core\SessionInterface;
use Core\View;

final class AuthController
{
	public function __construct(
		private readonly AuthService $auth,
		private readonly SessionInterface $session,
		private readonly View $view,
		private readonly Config $config
	){}

	/** GET /backoffice/login */
	public function create(Request $request): Response
	{
		return Response::html($this->view->renderPage('backoffice/login', [
			'error' => $this->session->getFlash('login_error'),
			'email' => $this->session->getFlash('login_email', '')
		]));
	}

	/** POST /backoffice/login */
	public function store(Request $request): Response
	{
		// boundary validation - inner layers trust these are clean strings
		$email = trim((string) $request->input('email', ''));
		$password = (string) $request->input('password', '');
		$remember = $request->input('remember') === '1';

		// empty submit fails fast and does NOT burn a rate-limit attempt
		if($email === '' || $password === ''){
			return $this->backToLogin('กรอก email และ password', $email);
		}

		$result = $this->auth->attempt($email, $password, $request->ip());

		return match($result){
			LoginResult::RateLimited => $this->backToLogin('ลองเข้าสู่ระบบผิดหลายครั้งเกินไป รอสักครู่แล้วลองใหม่', $email),
			LoginResult::Failed => $this->backToLogin('email หรือ password ไม่ถูกต้อง', $email),
			LoginResult::Success => $this->toDashboard($remember)
		};
	}

	/** POST /backoffice/logout */
	public function destroy(Request $request): Response
	{
		$cookieName = (string) $this->config->get('auth.remember_cookie', 'remember_me');

		// pass the cookie so its DB row dies with the session
		$this->auth->logout($request->cookie($cookieName));

		return Response::redirect('/backoffice/login')->withoutCookie($cookieName);
	}

	private function backToLogin(string $error, string $email): Response
	{
		// flash survives exactly one redirect (PRG), then vanishes
		$this->session->flash('login_error', $error);
		$this->session->flash('login_email', $email);

		return Response::redirect('/backoffice/login');
	}

	private function toDashboard(bool $remember): Response
	{
		$response = Response::redirect('/backoffice');

		if(!$remember){ return $response; }

		$token = $this->auth->issueRememberToken();

		if($token === null){ return $response; }

		$cookieName = (string) $this->config->get('auth.remember_cookie', 'remember_me');
		$days = (int) $this->config->get('auth.remember_lifetime_days', 30);

		return $response->withCookie($cookieName, $token, $days * 86400);
	}
}