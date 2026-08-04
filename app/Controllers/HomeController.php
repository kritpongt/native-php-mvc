<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Config;
use Core\Request;
use Core\Response;
use Core\View;

final class HomeController
{
	public function __construct(
		private readonly Config $config,
		private readonly View $view
	){}

	public function index(Request $request): Response
	{
		// return Response::html($this->view->renderPage('home/index', [
		// 	'env' => $this->config->get('app.env')
		// ]));
		return Response::html('<h1>Hello World</h1>');
	}

	// public function greet(Request $request): Response
	// {
	// 	$name = trim((string) $request->input('name', ''));

	// 	// htmx call -> fragment only, htmx swaps it into #greeting
	// 	if($request->isHtmx()){
	// 		return Response::html($this->view->renderPartial('greeting', [
	// 			'name' => $name
	// 		]));
	// 	}

	// 	return Response::html($this->view->renderPage('home/index', [
	// 		'env' => $this->config->get('app.env'),
	// 		'name' => $name
	// 	]));
	// }
}