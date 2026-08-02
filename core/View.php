<?php
declare(strict_types=1);

namespace Core;

use Twig\Environment;
use Twig\Extension\ExtensionInterface;
use Twig\Loader\FilesystemLoader;

final class View
{
	private readonly Environment $twig;
	private string $pagesDir = 'pages';
	private string $fragmentsDir = 'partials';
	private string $extension = '.html.twig';

	public function __construct(Config $config)
	{
		$viewPath = (string) $config->get('app.view_path');
		$debug = (bool) $config->get('app.debug');

		$this->twig = new Environment(new FilesystemLoader($viewPath), [
			// compiled templates = executable PHP -> storage/cache/twig
			'cache' => (string) $config->get('storage.twig_cache'),
			// debug on -> recompile when template file change (auto_reload)
			'debug' => $debug,
			'auto_reload' => $debug,
			// typo in a variable name = loud error, not silent empty output
			'strict_variables' => true,
			// project rule: autoescape ALWAYS ON ('html' is default - stated anyway)
			'autoescape' => 'html'
		]);
	}

	/** Register a Twig extension (i18n functions) before the first render. */
	public function addExtension(ExtensionInterface $extension): void
	{
		$this->twig->addExtension($extension);
	}

	/** Expose a value to every template (e.g. the resolved theme). */
	public function share(string $key, mixed $value): void
	{
		$this->twig->addGlobal($key, $value);
	}

	/** @param array<string, mixed> $data */
	public function render(string $template, array $data = []): string
	{
		return $this->twig->render($template, $data);
	}

	/** @param array<string, mixed> $data */
	public function renderPage(string $page, array $data = []): string
	{
		return $this->twig->render("{$this->pagesDir}/{$page}{$this->extension}", $data);
	}

	/** @param array<string, mixed> $data */
	public function renderPartial(string $partial, array $data = []): string
	{
		return $this->twig->render("{$this->fragmentsDir}/{$partial}{$this->extension}", $data);
	}
}