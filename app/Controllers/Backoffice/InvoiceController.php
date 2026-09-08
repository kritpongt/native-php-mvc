<?php
declare(strict_types=1);

namespace App\Controllers\Backoffice;

use App\Models\CustomerRepo;
use App\Models\DocumentRepo;
use App\Services\InvoiceService;
use App\Services\PdfService;
use Core\Request;
use Core\Response;
use Core\View;

final class InvoiceController
{
	public function __construct(
		private readonly InvoiceService $invoiceService,
		private readonly DocumentRepo $documentRepo,
		private readonly CustomerRepo $customerRepo,
		private readonly View $view,
		private readonly PdfService $pdfService
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
		$details = $this->invoiceService->getDetails((int) $id);

		if($request->isHtmx()){
			return Response::html($this->view->renderPartial('backoffice/invoice/show', $details));
		}

		return Response::html($this->view->renderPage('backoffice/invoice/show', $details));
	}

	public function streamPdf(Request $request, string $id): Response
	{
		$details = $this->invoiceService->getDetails((int) $id);
		$document_no = $details['document']->document_no;

		$data = [
			'document' => $details['document'],
			'customer' => $details['customer'],
			'items' => $details['items'],
			'baht_text' => $details['baht_text']
		];

		$data['logo_base64'] = $this->pdfService->getLocalAssetAsBase64('assets/imgs/logo2.jpg', 'image/jpeg');
		$data['font_reg_base64'] = $this->pdfService->getLocalAssetAsBase64('assets/fonts/Sarabun/Sarabun-Regular.ttf', 'font/truetype');
		$data['font_bold_base64'] = $this->pdfService->getLocalAssetAsBase64('assets/fonts/Sarabun/Sarabun-Bold.ttf', 'font/truetype');

		$pdfHeader = $this->view->renderPage('backoffice/quotation/_pdf_header', $data);
		$pdfFooter = $this->view->renderPage('backoffice/quotation/_pdf_footer', $data);
		$pdfHtml = $this->view->renderPage('backoffice/invoice/_pdf', $data);

		$this->pdfService->generateFromHtml($pdfHtml, "{$document_no}_invoice.pdf", true, $pdfHeader, $pdfFooter);

		return new Response('', 200, ['Content-Type' => 'application/pdf']);
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
