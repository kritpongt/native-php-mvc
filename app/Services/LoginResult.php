<?php
declare(strict_types=1);

namespace App\Services;

enum LoginResult
{
	case Success;
	// unknown email, wrong password, or inactive - caller must NOT distinguish
	case Failed;
	case RateLimited;
}