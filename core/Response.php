<?php
declare(strict_types=1);

namespace Core;

final class Response
{
	/**
	 * Queued Set-Cookie instructions, flushed in send().
	 * 
	 * @var list<array{name: string, value: string, maxAge: int, secure: bool}>
	 */
	private array $cookies = [];

	/** @param array<string, string> $headers */
	public function __construct(
		private string $body = '',
		private int $status = 200,
		private array $headers = []
	){}

	public static function html(string $body, int $status = 200): self
	{
		return new self($body, $status, ['Content-Type' => 'text/html; charset=UTF-8']);
	}

	public static function json(array $data, int $status = 200): self
	{
		return new self((string) json_encode($data), $status, ['Content-Type' => 'application/json; charset=UTF-8']);
	}

	/**
	 * 303 = "See Other": after POST, browser MUST switch to GET.
	 * Stops the "resubmit form?" popup on refresh (PRG pattern).
	 */
	public static function redirect(string $to, int $status = 303): self
	{
		return new self('', $status, ['Location' => $to]);
	}

	public static function notFound(string $body = '<h1>404 Not Found</h1>'): self
	{
		return new self($body, 404, ['Content-Type' => 'text/html; charset=UTF-8']);
	}
	
	/**
	 * Immutable: returns a COPY with the extra header.
	 * Middleware chains use this without side effects on the original.
	 */
	public function withHeader(string $name, string $value): self
	{
		$clone = clone $this;
		$clone->headers[$name] = $value;

		return $clone;
	}

	/**
	 * Immutable, like withHeader. $maxAge in seconds, 0 = session cookie.
	 * httponly + samesite=Lax always - same policy as the session cookie.
	 */
	public function withCookie(string $name, string $value, int $maxAge = 0, bool $secure = true): self
	{
		$clone = clone $this;
		$clone->cookies[] = ['name' => $name, 'value' => $value, 'maxAge' => $maxAge, 'secure' => $secure];

		return $clone;
	}

	/** Tell the browser to drop the cookie: empty value, expiry in the past. */
	public function withoutCookie(string $name): self
	{
		$clone = clone $this;
		$clone->cookies[] = ['name' => $name, 'value' => '', 'maxAge' => -31536000, 'secure' => true];

		return $clone;
	}

	public function status(): int
	{
		return $this->status;
	}

	public function body(): string
	{
		return $this->body;
	}
	
	/** @return array<string, string> */
	public function headers(): array
	{
		return $this->headers;
	}

	/**
	 * For unit tests - assert on queued cookies without sending.
	 * 
	 * @return list<array{name: string, value: string, maxAge: int, secure: bool}>
	 */
	public function cookies(): array
	{
		return $this->cookies;
	}

	/** The ONLY place in the whole app that echoes / calls header(), setcookie() */
	public function send(): void
	{
		http_response_code($this->status);

		foreach($this->headers as $name => $value){
			header("{$name}: {$value}", true);
		}

		foreach($this->cookies as $cookie){
			setcookie($cookie['name'], $cookie['value'], [
				// maxAge 0 = browser-session cookie; negative = delete now
				'expires' => $cookie['maxAge'] === 0 ? 0 : time() + $cookie['maxAge'],
				'path' => '/',
				'secure' => $cookie['secure'],
				'httponly' => true,
				'samesite' => 'Lax'
			]);
		}

		echo $this->body;
	}
}