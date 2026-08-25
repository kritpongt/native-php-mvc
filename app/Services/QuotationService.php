<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\CustomerRepo;
use App\Models\Document;
use App\Models\DocumentItemRepo;
use App\Models\DocumentRepo;
use Core\Database;
use RuntimeException;

final class QuotationService
{
	public function __construct(
		private readonly DocumentRepo $documentRepo,
		private readonly DocumentItemRepo $itemRepo,
		private readonly CustomerRepo $customerRepo,
		private readonly InvoiceService $invoiceService,
		private readonly Database $db
	){}

	/**
	 * @return array{document: Document, items: list<\App\Models\DocumentItem>, customer: \App\Models\Customer|null}
	 */
	public function getDetails(int $id): array
	{
		$document = $this->documentRepo->findById($id);
		if($document === null){
			throw new RuntimeException('Document not found');
		}

		$items = $this->itemRepo->findByDocumentId($id);
		$customer = $this->customerRepo->findById($document->customer_id);

		return [
			'document' => clone $document,
			'items' => $items,
			'customer' => $customer
		];
	}

	/**
	 * @param array<string, mixed> $docData
	 * @param list<array<string, mixed>> $itemsData
	 */
	public function createQuotationWithItems(array $docData, array $itemsData): Document
	{
		$subtotal = 0.0;
		foreach($itemsData as $item){
			$subtotal += ($item['quantity'] * $item['unit_price']);
		}

		$discount = (float) ($docData['discount'] ?? 0);
		$subtotalAfterDiscount = $subtotal - $discount;
		$vatRate = (float) ($docData['vat_rate'] ?? 7.0);
		
		$vat = $vatRate > 0 ? ($subtotalAfterDiscount * $vatRate / 100) : 0;
		$grandTotal = $subtotalAfterDiscount + $vat;

		$docData['subtotal'] = $subtotal;
		$docData['vat'] = $vat;
		$docData['grand_total'] = $grandTotal;

		// DB Transaction
		return $this->db->transaction(function() use($docData, $itemsData){
			if(empty($docData['document_no'])){
				$docData['document_no'] = $this->documentRepo->generateNo($docData['type']);
			}

			$document = $this->documentRepo->create($docData);

			foreach($itemsData as $item){
				$item['document_id'] = $document->id;
				$item['total_price'] = $item['quantity'] * $item['unit_price'];
				$this->itemRepo->create($item);
			}

			return $document;
		});
	}

	public function updateStatus(int $id, string $newStatus): void
	{
		$document = $this->documentRepo->findById($id);
		if($document === null || $document->type !== 'quotation'){
			throw new RuntimeException('Quotation not found');
		}

		if(!in_array($newStatus, Document::STATUSES)){
			throw new \InvalidArgumentException('Invalid status');
		}

		// state transitions
		$validTransitions = [
			'draft' => ['issued', 'approved', 'cancelled'],
			'issued' => ['draft', 'approved', 'cancelled'],
			// final state: can't change
			'approved' => [],
			'cancelled' => [],
		];

		$currentStatus = $document->status;
		if($currentStatus === $newStatus){ return; }

		if(!in_array($newStatus, $validTransitions[$currentStatus] ?? [])){
			throw new RuntimeException("Cannot change status from '{$currentStatus}' to '{$newStatus}'");
		}

		$this->documentRepo->updateStatus($id, $newStatus);

		if($newStatus === 'approved'){
			$this->invoiceService->createFromQuotation($id);
		}
	}
}