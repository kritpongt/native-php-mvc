<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentRepo;
use App\Models\DocumentItemRepo;
use App\Models\CustomerRepo;
use Core\Database;
use RuntimeException;

final class DocumentService
{
	public function __construct(
		private readonly DocumentRepo $documentRepo,
		private readonly DocumentItemRepo $itemRepo,
		private readonly CustomerRepo $customerRepo,
		private readonly Database $db
	){}

	/**
	 * @return array{document: Document, items: list<\App\Models\DocumentItem>, customer: \App\Models\Customer|null}
	 */
	public function getDocumentDetails(int $id): array
	{
		$document = $this->documentRepo->findById($id);
		if($document === null){
			throw new RuntimeException('Document not found');
		}

		$items = $this->itemRepo->findByDocumentId($id);
		$customer = $this->customerRepo->findById($document->customer_id);

		return [
			'document' => clone $document, // Can't easily attach to readonly properties without reflection, returning array is safer
			'items' => $items,
			'customer' => $customer
		];
	}

	/**
	 * @param array<string, mixed> $docData
	 * @param list<array<string, mixed>> $itemsData
	 */
	public function createDocumentWithItems(array $docData, array $itemsData): Document
	{
		// Calculate totals just to be safe
		$subtotal = 0.0;
		foreach($itemsData as $item){
			$subtotal += ($item['quantity'] * $item['unit_price']);
		}

		$discount = (float) ($docData['discount'] ?? 0);
		$subtotalAfterDiscount = $subtotal - $discount;
		$vatRate = (float) ($docData['vat_rate'] ?? 7.0); // usually 7% in TH
		
		// 0 for no vat, else calculate
		$vat = $vatRate > 0 ? ($subtotalAfterDiscount * $vatRate / 100) : 0;
		$grandTotal = $subtotalAfterDiscount + $vat;

		$docData['subtotal'] = $subtotal;
		$docData['vat'] = $vat;
		$docData['grand_total'] = $grandTotal;

		// Start Transaction
		$this->db->beginTransaction();
		
		try {
			if(empty($docData['document_no'])){
				$docData['document_no'] = $this->generateDocumentNo($docData['type']);
			}

			$document = $this->documentRepo->create($docData);

			foreach($itemsData as $item){
				$item['document_id'] = $document->id;
				$item['total_price'] = $item['quantity'] * $item['unit_price'];
				$this->itemRepo->create($item);
			}

			$this->db->commit();
			return $document;
			
		} catch (\Throwable $e) {
			$this->db->rollBack();
			throw $e;
		}
	}

	public function generateDocumentNo(string $type): string
	{
		$prefix = match($type){
			'quotation' => 'QU',
			'delivery' => 'DO',
			'invoice' => 'INV',
			default => 'DOC'
		};

		$yearMonth = date('Ym'); // e.g., 202608
		$searchPrefix = "{$prefix}-{$yearMonth}-%";

		// Find the latest document number for this month
		$row = $this->db->selectOne(
			'SELECT document_no FROM documents WHERE document_no LIKE :prefix ORDER BY id DESC LIMIT 1',
			['prefix' => $searchPrefix]
		);

		if($row === null){
			return "{$prefix}-{$yearMonth}-001";
		}

		// Example: QU-202608-001 -> Extract '001' and increment
		$parts = explode('-', (string) $row['document_no']);
		$lastNumber = (int) end($parts);
		$newNumber = str_pad((string) ($lastNumber + 1), 3, '0', STR_PAD_LEFT);

		return "{$prefix}-{$yearMonth}-{$newNumber}";
	}
}
