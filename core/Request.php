<?php
declare(strict_types=1);

namespace Core;

final class Request
{
	/**
	 * Public constructor on purpose - unit tests build fake request with it.
	 * 
	 * @param array<string, mixed> $query from $_GET
	 * @param array<string, mixed> $body from $_POST
	 * @param array<string, string> $headers lowercase-dash keys: 'hx-request'
	 * @param array<string, string> $cookies from $_COOKIE
	 */
	public function __construct(
		private readonly string $method,
		private readonly string $path,
		private readonly array $query,
		private readonly array $body,
		private readonly array $headers,
		private readonly array $cookies,
		private readonly string $ip
	){}

	public static function fromGlobals(): self
	{
		$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

		// HTML Forms only speak GET/POST. Hidden <input name="_method">
		// unlocks PUT/PATCH/DELETE without JS (progressive enhancement).
		// Only *POST* may override - GET override would be a security hole.
		if($method === 'POST'){
			$override = strtoupper((string) ($_POST['_method'] ?? ''));
			if(in_array($override, ['PUT', 'PATCH', 'DELETE'], true)){
				$method = $override;
			}
		}

		// strip query string: /users?page=2 -> /users
		$uri = $_SERVER['REQUEST_URI'] ?? '/';
		$path = parse_url($uri, PHP_URL_PATH) ?: '/';

		return new self(
			method: $method,
			path: $path,
			query: $_GET,
			body: $_POST,
			headers: self::collectHeaders(),
			cookies: $_COOKIE,
			ip: $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0' // *REMOTE_ADDR* only - X-Forwarded-For is client-controlled, spoofable
		);
	}

	public function method(): string
	{
		return $this->method;
	}

	public function path(): string
	{
		return $this->path;
	}

	/**
	 * Immutable copy with a rewritten path.
	 * ResolveLocale strip the /{locale} prefix before the router matches.
	 */
	public function withPath(string $path): self
	{
		return new self(
			method: $this->method,
			path: $path,
			query: $this->query,
			body: $this->body,
			headers: $this->headers,
			cookies: $this->cookies,
			ip: $this->ip
		);
	}

	public function query(string $key, mixed $default = null): mixed
	{
		return $this->query[$key] ?? $default;
	}

	public function input(string $key, mixed $default = null): mixed
	{
		return $this->body[$key] ?? $default;
	}

	/** @return array<string, mixed> */
	public function all(): array
	{
		return $this->body;
	}

	public function header(string $name, ?string $default = null): ?string
	{
		// case-insensitive lookup: HX-Request == hx-request
		return $this->headers[strtolower($name)] ?? $default;
	}

	public function cookie(string $name, ?string $default = null): ?string
	{
		return isset($this->cookies[$name]) ? (string) $this->cookies[$name] : $default;
	}

	public function ip(): string
	{
		return $this->ip;
	}

	/** HTMX sends 'HX-Request: true' on every ajax call */
	public function isHtmx(): bool
	{
		return $this->header('hx-request') === 'true';
	}

	/** @return array<string, string> */
	private static function collectHeaders(): array
	{
		$headers = [];

		foreach($_SERVER as $key => $value){
			// PHP stores headers as HTTP_HX_REQUEST -> we want 'hx-request'
			if(str_starts_with($key, 'HTTP_')){
				$name = strtolower(str_replace('_', '-', substr($key, 5)));
				$headers[$name] = (string) $value;
			}
		}

		// these two skip the HTTP_ prefix - PHP quirk
		if(isset($_SERVER['CONTENT_TYPE'])){
			$headers['content-type'] = (string) $_SERVER['CONTENT_TYPE'];
		}
		if(isset($_SERVER['CONTENT_LENGTH'])){
			$headers['content-length'] = (string) $_SERVER['CONTENT_LENGTH'];
		}

		return $headers;
	}
}
