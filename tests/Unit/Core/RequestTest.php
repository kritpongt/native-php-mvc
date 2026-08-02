<?php
declare(strict_types=1);

namespace Tests\Unit\Core;

use Core\Request;
use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase
{
	/** @param array<string, string> $headers */
	private function makeRequest(array $headers = []): Request
	{
		// the public constructor earns its keep: fake request, no superglobals
		return new Request('GET', '/', [], [], $headers, [], '127.0.0.1');
	}

	public function test_header_lookup_is_case_insensitive(): void
	{
		$request = $this->makeRequest(['hx-request' => 'true']);

		$this->assertSame('true', $request->header('HX-Request'));
	}

	public function test_is_htmx(): void
	{
		$this->assertTrue($this->makeRequest(['hx-request' => 'true'])->isHtmx());
		$this->assertFalse($this->makeRequest()->isHtmx());
	}

	public function test_input_falls_back_to_default(): void
	{
		$request = new Request('POST', '/x', [], ['name' => 'abc'], [], [], '127.0.0.1');

		$this->assertSame('abc', $request->input('name'));
		$this->assertSame('xxx', $request->input('nope', 'xxx'));
	}
}