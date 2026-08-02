<?php
declare(strict_types=1);

namespace App\Middlewares;

use Closure;
use Core\Csrf;
use Core\Middleware;
use Core\Request;
use Core\Response;
use Core\Translator;
use Core\View;

final class ShareViewData implements Middleware
{
	public function __construct(
		private readonly View $view,
		private readonly Csrf $csrf,
		private readonly Translator $translator 
	){}

	public function handle(Request $request, Closure $next): Response
	{
		// sits AFTER StartSession in the pipeline -> session is live here,
		// so minting the csrf token (which writes the session) is safe.
		$this->view->share('csrf_token', $this->csrf->token());

		// '' = no explicit choice -> layout omits data-theme -> CSS follows the OS.
		$theme = $request->cookie('theme');
		$this->view->share('theme', in_array($theme, ['light', 'dark'], true) ? $theme : '');

		$this->view->share('current_path', $request->path());
		$this->view->share('locales', $this->translator->supported());
		$this->view->share('current_locale', $this->translator->locale());

		return $next($request);
	}
}