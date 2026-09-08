<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\CustomerRepo;
use App\Models\Document;
use App\Models\DocumentItemRepo;
use App\Models\DocumentRepo;
use App\Utils\NumberHelper;
use Core\Database;
use RuntimeException;

final class InvoiceService
{
	public function __construct(
		private readonly Database $db,
		private readonly DocumentRepo $documentRepo,
		private readonly DocumentItemRepo $itemRepo,
		private readonly CustomerRepo $customerRepo,
	){}

	public function getDetails(int $id): array
	{
		$document = $this->documentRepo->findByIdAndType($id, 'invoice');
		if($document === null){
			throw new RuntimeException('Invoice not found');
		}

		$items = $this->itemRepo->findByDocumentId($id);
		$customer = $this->customerRepo->findById($document->customer_id);

		return [
			'document' => clone $document,
			'items' => $items,
			'baht_text' => NumberHelper::bahtText($document->grand_total),
			'customer' => $customer
		];
	}

	public function createFromQuotation(int $quotationId): Document
	{
		$quotation = $this->documentRepo->findById($quotationId);

		if($quotation === null || $quotation->type !== 'quotation'){
			throw new RuntimeException('Quotation not found');
		}

		if($quotation->status !== 'approved'){
			throw new RuntimeException('Can only create invoice from approved quotation');
		}

		$existingInvoice = $this->documentRepo->findByReferenceId($quotationId);
		if($existingInvoice !== null){
			throw new RuntimeException('Invoice already exists for this Quotation (Document No: '.$quotation->document_no.')');
		}

		$items = $this->itemRepo->findByDocumentId($quotationId);

		// DB Transaction
		return $this->db->transaction(function() use($quotation, $items){
			$issueDate = date('Y-m-d');
			$dueDate = date('Y-m-d', strtotime('+30 days'));

			$invoiceData = [
				'document_no' => $this->documentRepo->generateNo('invoice'),
				'customer_id' => $quotation->customer_id,
				'type' => 'invoice',
				'status' => 'draft',
				'issue_date' => $issueDate,
				'due_date' => $dueDate,
				'reference_id'=> $quotation->id,
				'subtotal' => $quotation->subtotal,
				'discount' => $quotation->discount,
				'vat' => $quotation->vat,
				'grand_total' => $quotation->grand_total,
				'notes' => 'Reference Document No: ' . $quotation->document_no,
			];

			$invoice = $this->documentRepo->create($invoiceData);

			foreach($items as $item){
				$this->itemRepo->create([
					'document_id' => $invoice->id,
					'name' => $item->name,
					'description' => $item->description,
					'quantity' => $item->quantity,
					'unit_price' => $item->unit_price,
					'total_price' => $item->total_price
				]);
			}

			return $invoice;
		});
	}

	public function updateStatus(int $id, string $newStatus): void
	{
		$document = $this->documentRepo->findById($id);
		if($document === null || $document->type !== 'invoice'){
			throw new RuntimeException('Invoice not found');
		}

		if(!in_array($newStatus, Document::INV_STATUSES)){
			throw new \InvalidArgumentException('Invalid status');
		}

		// state transitions
		$validTransitions = [
			'draft' => ['unpaid', 'paid', 'cancelled'],
			'unpaid' => ['draft', 'paid', 'cancelled'],
			'paid' => ['draft', 'unpaid', 'cancelled'],
			'cancelled' => [] // final state: can't change
		];

		$currentStatus = $document->status;
		if($currentStatus === $newStatus){ return; }

		if(!in_array($newStatus, $validTransitions[$currentStatus] ?? [])){
			throw new RuntimeException("Cannot change status from '{$currentStatus}' to '{$newStatus}'");
		}

		if($newStatus === 'paid'){
			// $this->paymentService
		}

		$this->documentRepo->updateStatus($id, $newStatus);
	}
}