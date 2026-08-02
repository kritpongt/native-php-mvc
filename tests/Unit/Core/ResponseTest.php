<?php
declare(strict_types=1);

namespace Tests\Unit\Core;

use Core\Response;
use PHPUnit\Framework\TestCase;

final class ResponseTest extends TestCase
{
	public function test_html_sets_body_status_and_content_type(): void
	{
		$response = Response::html('<p>hi</p>', 201);

		$this->assertSame('<p>hi</p>', $response->body());
		$this->assertSame(201, $response->status());
		$this->assertSame('text/html; charset=UTF-8', $response->headers()['Content-Type']);
	}

	public function test_with_header_returns_copy_original_untouched(): void
	{
		$original = Response::html('x');
		$stamped = $original->withHeader('X-Frame-Options', 'DENY');

		$this->assertArrayHasKey('X-Frame-Options', $stamped->headers());
		$this->assertArrayNotHasKey('X-Frame-Options', $original->headers());
	}

	public function test_redirect_defaults_to_303(): void
	{
		$response = Response::redirect('/login');

		$this->assertSame(303, $response->status());
		$this->assertSame('/login', $response->headers()['Location']);
	}
}