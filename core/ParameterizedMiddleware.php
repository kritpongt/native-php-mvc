<?php
declare(strict_types=1);

namespace Core;

interface ParameterizedMiddleware extends Middleware
{
	/**
	 * Must return a CLONE carrying the params.
	 * The container-held instance stays clean for the next route.
	 */
	public function withParams(string ...$params): static;
}