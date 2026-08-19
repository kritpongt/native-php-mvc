<?php
declare(strict_types=1);

namespace App\Controllers\Backoffice;

use Core\Request;
use Core\Response;
use App\Services\DocumentService;
use App\Models\DocumentRepo;
use App\Models\CustomerRepo;

final class DocumentController
{
	public function __construct(
		private readonly DocumentService $documentService,
		private readonly DocumentRepo $documentRepo,
		private readonly CustomerRepo $customerRepo
	){}

	public function index(Request $request): Response
	{
		$type = $request->query('type') ?? 'quotation'; // default list quotations
		$documents = $this->documentRepo->findByType($type);

		return Response::view('pages/backoffice/documents/index.html.twig', [
			'documents' => $documents,
			'type' => $type
		]);
	}

	public function create(Request $request): Response
	{
		$type = $request->query('type') ?? 'quotation';
		$customers = $this->customerRepo->all();

		return Response::view('pages/backoffice/documents/create.html.twig', [
			'type' => $type,
			'customers' => $customers
		]);
	}

	public function store(Request $request): Response
	{
		// Validation should happen here or via a dedicated validation service
		$docData = [
			'customer_id' => (int) $request->post('customer_id'),
			'type' => $request->post('type'), // 'quotation', 'invoice', etc.
			'status' => 'draft',
			'issue_date' => $request->post('issue_date'),
			'due_date' => $request->post('due_date') ?: null,
			'discount' => (float) $request->post('discount'),
			'vat_rate' => (float) $request->post('vat_rate'),
			'notes' => $request->post('notes'),
		];

		// In a real scenario, this comes from an array of inputs from the frontend
		// E.g. name[], quantity[], unit_price[]
		$names = $request->postArray('item_name');
		$quantities = $request->postArray('item_quantity');
		$prices = $request->postArray('item_price');

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

		$this->documentService->createDocumentWithItems($docData, $itemsData);

		return Response::redirect('/backoffice/documents?type=' . $docData['type']);
	}

	public function show(Request $request, array $vars): Response
	{
		$id = (int) $vars['id'];
		$details = $this->documentService->getDocumentDetails($id);

		return Response::view('pages/backoffice/documents/show.html.twig', $details);
	}
}
