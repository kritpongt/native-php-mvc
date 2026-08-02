<?php
declare(strict_types=1);

namespace Core;

final class Session implements SessionInterface
{
	private bool $started = false;

	public function __construct(private readonly Config $config){}

	/** call once per request BEFORE any session read/write (middleware's job) */
	public function start(): void
	{
		if($this->started || session_status() === PHP_SESSION_ACTIVE){ return; }

		// never accept a session id from the URL - fixation door #1
		ini_set('session.use_only_cookies', '1');
		// reject ids the server never issued - fixation door #2
		ini_set('session.use_strict_mode', '1');
	
		session_name((string) $this->config->get('session.name', 'app_session'));

		session_set_cookie_params([
			'lifetime' => (int) $this->config->get('session.lifetime', 0),
			'path' => '/',
			'httponly' => true, 	// JS cannot read the cookie - blunts XSS
			'secure' => (bool) $this->config->get('session.secure', true),
			'samesite' => 'Lax' 	// cross-site POST arrives WITHOUT the cookie
		]);

		session_start();
		$this->started = true;
	}

	public function get(string $key, mixed $default = null): mixed
	{
		return $_SESSION[$key] ?? $default;
	}

	public function set(string $key, mixed $value): void
	{
		$_SESSION[$key] = $value;
	}

	public function has(string $key): bool
	{
		return isset($_SESSION[$key]);
	}

	public function remove(string $key): void
	{
		unset($_SESSION[$key]);
	}

	/** new id, old id dies. project rule: call after EVERY login/logout */
	public function regenerate(): void
	{
		session_regenerate_id(true);
	}

	public function invalidate(): void
	{
		$_SESSION = [];
		$this->regenerate();
	}

	/** flash = value lives until read ONCE ("saved!", login error) */
	public function flash(string $key, mixed $value): void
	{
		$_SESSION['_flash'][$key] = $value;
	}

	public function getFlash(string $key, mixed $default = null): mixed
	{
		$value = $_SESSION['_flash'][$key] ?? $default;
		unset($_SESSION['_flash'][$key]);

		return $value;
	}
}