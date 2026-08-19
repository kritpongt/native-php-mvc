<?php
declare(strict_types=1);

namespace App\Middlewares;

use App\Services\AuthService;
use Closure;
use Core\Config;
use Core\Middleware;
use Core\Request;
use Core\Response;
use Core\View;

final class Auth implements Middleware
{
	public function __construct(
		private readonly AuthService $auth,
		private readonly Config $config,
		private readonly View $view
	){}

	public function handle(Request $request, Closure $next): Response
	{
		if($this->auth->check()){
			$this->view->share('user', $this->auth->user());
			return $next($request);
		}

		// no session - the remember cookie gets ONE chance
		$cookieName = (string) $this->config->get('auth.remember_cookie', 'remember_me');
		$cookieValue = $request->cookie($cookieName);

		if($cookieValue === null){
			return $this->toLogin($request);
		}

		$rotated = $this->auth->loginFromCookie($cookieValue);

		if($rotated === null){
			// dead cookie (expired / stolen / user inactive) - clear it
			return $this->toLogin($request)->withoutCookie($cookieName);
		}

		$this->view->share('user', $this->auth->user());

		$days = (int) $this->config->get('auth.remember_lifetime_days', 30);

		// logged in via cookie - continue, browser receives the rotated token
		return $next($request)->withCookie($cookieName, $rotated, $days * 86400);
	}

	private function toLogin(Request $request): Response
	{
		// htmx ajax ignores a 303 Location - HX-Redirect forces a full-page hop
		return $request->isHtmx()
			? (new Response('', 204))->withHeader('HX-Redirect', '/backoffice/login')
			: Response::redirect('/backoffice/login');
	}
}