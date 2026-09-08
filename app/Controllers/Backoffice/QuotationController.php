<?php
declare(strict_types=1);

namespace App\Controllers\Backoffice;

use App\Models\CustomerRepo;
use App\Models\DocumentRepo;
use App\Services\PdfService;
use App\Services\QuotationService;
use Core\Request;
use Core\Response;
use Core\View;
use RuntimeException;

final class QuotationController
{
	public function __construct(
		private readonly QuotationService $quotationService,
		private readonly DocumentRepo $documentRepo,
		private readonly CustomerRepo $customerRepo,
		private readonly View $view,
		private readonly PdfService $pdfService
	){}

	public function index(Request $request): Response
	{
		$type = $request->query('type') ?? 'quotation'; // default list quotations
		$documents = $this->documentRepo->findByType($type);
		
		$data = [
			'documents' => $documents,
			'statuses' => \App\Models\Document::STATUSES,
			'type' => $type
		];

		if($request->isHtmx()){
			return Response::html($this->view->renderPartial('backoffice/quotation/index', $data));
		}

		return Response::html($this->view->renderPage('backoffice/quotation/index', $data));
	}

	/** GET /quotation/create */
	public function create(Request $request): Response
	{
		$customers = $this->customerRepo->all();
		
		$data = [
			'customers' => $customers
		];

		if($request->isHtmx()){
			return Response::html($this->view->renderPartial('backoffice/quotation/create', $data));
		}

		return Response::html($this->view->renderPage('backoffice/quotation/create', $data));
	}

	/** POST /quotation - store */
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
			'title' => $request->input('title'),
			'notes' => $request->input('notes'),
		];

		$item_names = $request->input('item_name') ?? [];
		$quantities = $request->input('item_quantity') ?? [];
		$prices = $request->input('item_price') ?? [];
		$labor_prices = $request->input('labor_price') ?? [];

		$itemsData = [];
		if (is_array($item_names)) {
			foreach ($item_names as $index => $name) {
				if (!empty($name)) {
					$itemsData[] = [
						'name' => $name,
						'quantity' => (float) ($quantities[$index] ?? 1),
						'unit_price' => (float) ($prices[$index] ?? 0),
						'labor_price' => (float) ($labor_prices[$index] ?? 0),
					];
				}
			}
		}

		$this->quotationService->createQuotationWithItems($quotationData, $itemsData);

		return $request->isHtmx()
			? (new Response('', 204))->withHeader('HX-Location', '{"path": "/backoffice/quotation", "target": "#main-content"}')
			: Response::redirect('/backoffice/quotation');
	}

	public function show(Request $request, string $id): Response
	{
		$docId = (int) $id;
		$details = $this->quotationService->getDetails($docId);

		if($request->isHtmx()){
			return Response::html($this->view->renderPartial('backoffice/quotation/show', $details));
		}

		return Response::html($this->view->renderPage('backoffice/quotation/show', $details));
	}

	public function streamPdf(Request $request, string $id): Response
	{
		$details = $this->quotationService->getDetails((int) $id);
		$document_no = $details['document']->document_no;

		$data = [
			'document' => $details['document'],
			'customer' => $details['customer'],
			'items' => $details['items'],
		];

		$data['logo_base64'] = $this->pdfService->getLocalAssetAsBase64('assets/imgs/logo2.jpg', 'image/jpeg');
		$data['font_reg_base64'] = $this->pdfService->getLocalAssetAsBase64('assets/fonts/Sarabun/Sarabun-Regular.ttf', 'font/truetype');
		$data['font_bold_base64'] = $this->pdfService->getLocalAssetAsBase64('assets/fonts/Sarabun/Sarabun-Bold.ttf', 'font/truetype');

		$pdfHeader = $this->view->renderPage('backoffice/quotation/_pdf_header', $data);
		$pdfFooter = $this->view->renderPage('backoffice/quotation/_pdf_footer', $data);
		$pdfHtml = $this->view->renderPage('backoffice/quotation/_pdf', $data);

		$this->pdfService->generateFromHtml($pdfHtml, "{$document_no}_quotation.pdf", true, $pdfHeader, $pdfFooter);

		return new Response('', 200, ['Content-Type' => 'application/pdf']);
	}

	public function edit(Request $request, string $id): Response
	{
		$docId = ctype_digit($id) ? (int) $id : 0;
		$details = $this->quotationService->getDetails($docId);
		$customers = $this->customerRepo->all();

		$data = [
			'targetDoc' => $details['document'],
			'items' => $details['items'],
			'customers' => $customers
		];

		if ($request->isHtmx()) {
			return Response::html($this->view->renderPartial("backoffice/quotation/edit", $data));
		}

		return Response::html($this->view->renderPage("backoffice/quotation/edit", $data));
	}

	public function update(Request $request, string $id): Response
	{
		$docId = ctype_digit($id) ? (int) $id : 0;

		$quotationData = [
			'customer_id' => (int) $request->input('customer_id'),
			'type' => 'quotation',
			'status' => 'draft',
			'issue_date' => $request->input('issue_date'),
			'due_date' => $request->input('due_date') ?: null,
			'discount' => (float) $request->input('discount', 0),
			'vat_rate' => (float) $request->input('vat_rate', 7),
			'title' => $request->input('title'),
			'notes' => $request->input('notes'),
		];

		$errors = [];

		$item_names = $request->input('item_name') ?? [];
		$quantities = $request->input('item_quantity') ?? [];
		$prices = $request->input('item_price') ?? [];
		$labor_prices = $request->input('labor_price') ?? [];

		$itemsData = [];
		if (is_array($item_names)) {
			foreach ($item_names as $index => $name) {
				if (!empty($name)) {
					$itemsData[] = [
						'name' => $name,
						'quantity' => (float) ($quantities[$index] ?? 1),
						'unit_price' => (float) ($prices[$index] ?? 0),
						'labor_price' => (float) ($labor_prices[$index] ?? 0),
					];
				}
			}
		}

		$this->quotationService->updateQuotationWithItems($docId, $quotationData, $itemsData);

		return $request->isHtmx()
			? (new Response('', 204))->withHeader('HX-Location', '{"path": "/backoffice/quotation", "target": "#main-content"}')
			: Response::redirect('/backoffice/quotation');
	}

	// private function backToEdit(Request $request, array $errors, int $docId): Response
	// {
	// }

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