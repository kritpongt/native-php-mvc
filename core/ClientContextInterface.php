<?php
declare(strict_types=1);

namespace Core;

interface ClientContextInterface
{
	public function ip(): string;
}