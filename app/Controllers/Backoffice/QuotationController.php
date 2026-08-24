<?php
declare(strict_types=1);

namespace App\Controllers\Backoffice;

use App\Models\CustomerRepo;
use App\Models\DocumentRepo;
use App\Services\QuotationService;
use Core\Request;
use Core\Response;
use Core\View;

final class QuotationController
{
	public function __construct(
		private readonly QuotationService $quotationService,
		private readonly DocumentRepo $documentRepo,
		private readonly CustomerRepo $customerRepo,
		private readonly View $view
	){}

	public function index(Request $request): Response
	{
		$type = $request->query('type') ?? 'quotation'; // default list quotations
		$documents = $this->documentRepo->findByType($type);
		
		$data = [
			'documents' => $documents,
			'type' => $type
		];

		if($request->isHtmx()){
			return Response::html($this->view->renderPartial('backoffice/quotation/index', $data));
		}

		return Response::html($this->view->renderPage('backoffice/quotation/index', $data));
	}

	public function create(Request $request): Response
	{
		$type = $request->query('type') ?? 'quotation';
		$customers = $this->customerRepo->all();
		
		$data = [
			'type' => $type,
			'customers' => $customers
		];

		if($request->isHtmx()){
			return Response::html($this->view->renderPartial('backoffice/quotation/create', $data));
		}

		return Response::html($this->view->renderPage('backoffice/quotation/create', $data));
	}

	/** POST /quotation */
	public function store(Request $request): Response
	{
		$customerId = (int) $request->input('customer_id');

		if ($request->input('is_new_customer') === '1') {
			$customer = $this->customerRepo->create(
				name: (string) $request->input('new_customer_name'),
				tax_id: $request->input('new_customer_tax_id') ?: null,
				address: $request->input('new_customer_address') ?: null,
				phone: $request->input('new_customer_phone') ?: null,
				email: null
			);
			$customerId = $customer->id;
		}

		$quotationData = [
			'customer_id' => $customerId,
			'type' => 'quotation',
			'status' => 'draft',
			'issue_date' => $request->input('issue_date'),
			'due_date' => $request->input('due_date') ?: null,
			'discount' => (float) $request->input('discount', 0),
			'vat_rate' => (float) $request->input('vat_rate', 7),
			'notes' => $request->input('notes'),
		];

		$names = $request->input('item_name') ?? [];
		$quantities = $request->input('item_quantity') ?? [];
		$prices = $request->input('item_price') ?? [];

		$itemsData = [];
		if (is_array($names)) {
			foreach ($names as $index => $name) {
				if (!empty($name)) {
					$itemsData[] = [
						'name' => $name,
						'quantity' => (float) ($quantities[$index] ?? 1),
						'unit_price' => (float) ($prices[$index] ?? 0),
					];
				}
			}
		}

		$this->quotationService->createDocumentWithItems($quotationData, $itemsData);

		return $request->isHtmx()
			? (new Response('', 204))->withHeader('HX-Location', '{"path": "/backoffice/quotation", "target": "#main-content"}')
			: Response::redirect('/backoffice/quotation');
	}

	public function show(Request $request, string $id): Response
	{
		$docId = (int) $id;
		$details = $this->quotationService->getDocumentDetails($docId);

		if($request->isHtmx()){
			return Response::html($this->view->renderPartial('backoffice/quotation/show', $details));
		}

		return Response::html($this->view->renderPage('backoffice/quotation/show', $details));
	}

	public function status(Request $request, string $id): Response
	{
		$docId = (int) $id;
		$newStatus = (string) $request->input('status');

		$this->quotationService->updateStatus($docId, $newStatus);

		return $request->isHtmx()
			? (new Response('', 204))->withHeader('HX-Location', '{"path": "/backoffice/quotation", "target": "#main-content"}')
			: Response::redirect('/backoffice/quotation');
	}
}