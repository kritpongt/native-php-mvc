<?php
declare(strict_types=1);

namespace App\Controllers\Backoffice;

use App\Models\CustomerRepo;
use App\Models\DocumentRepo;
use App\Services\InvoiceService;
use Core\Request;
use Core\Response;
use Core\View;

final class InvoiceController
{
	public function __construct(
		private readonly InvoiceService $invoiceService,
		private readonly DocumentRepo $documentRepo,
		private readonly CustomerRepo $customerRepo,
		private readonly View $view
	){}

	public function index(Request $request): Response
	{
		$documents = $this->documentRepo->findByType('invoice');
		
		$data = [
			'documents' => $documents,
			'statuses' => \App\Models\Document::INV_STATUSES,
			'type' => 'invoice'
		];

		if($request->isHtmx()){
			return Response::html($this->view->renderPartial('backoffice/invoice/index', $data));
		}

		return Response::html($this->view->renderPage('backoffice/invoice/index', $data));
	}

	public function show(Request $request, string $id): Response
	{
		$docId = (int) $id;
		$document = $this->documentRepo->findById($docId);
		
		if($document === null || $document->type !== 'invoice'){
			return Response::redirect('/backoffice/invoice');
		}

		$items = (new \App\Models\DocumentItemRepo((new \Core\Database(\Core\Config::getInstance()))))->findByDocumentId($docId);
		$customer = $this->customerRepo->findById($document->customer_id);

		$details = [
			'document' => clone $document,
			'items' => $items,
			'customer' => $customer
		];

		if($request->isHtmx()){
			return Response::html($this->view->renderPartial('backoffice/invoice/show', $details));
		}

		return Response::html($this->view->renderPage('backoffice/invoice/show', $details));
	}

	public function status(Request $request, string $id): Response
	{
		$docId = (int) $id;
		$newStatus = (string) $request->input('status');

		$this->invoiceService->updateStatus($docId, $newStatus);

		return $request->isHtmx()
			? (new Response('', 204))->withHeader('HX-Location', '{"path": "/backoffice/invoice", "target": "#main-content"}')
			: Response::redirect('/backoffice/invoice');
	}
}
