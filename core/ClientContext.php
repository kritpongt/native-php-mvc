<?php
declare(strict_types=1);

namespace Core;

final class ClientContext implements ClientContextInterface
{
	public function ip(): string
	{
		// Cloudflare
		if(!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
			return $_SERVER['HTTP_CF_CONNECTING_IP'];
		}

		return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
	}
}