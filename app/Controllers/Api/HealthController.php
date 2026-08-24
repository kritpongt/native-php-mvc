<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use Core\Database;
use Core\Request;
use Core\Response;

final class HealthController
{
 public function __construct(private readonly Database $db){}

 public function index(Request $request): Response
 {
	try{
		$this->db->selectOne('SELECT 1');

		return Response::json([
			'success' => true,
			'data' => [
				'status' => 'up',
				'database' => 'connected',
				'timestamp' => time()
			]
		]);
	}catch(\Throwable $e){
		return Response::json([
			'success' => false,
			'error' => 'Service Unvailable: Database connection failed'
		], 503);
	}
 }
}