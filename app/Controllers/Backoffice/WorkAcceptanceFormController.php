<?php
declare(strict_types=1);

namespace App\Controllers\Backoffice;

use App\Models\DocumentRepo;
use App\Services\PdfService;
use App\Services\QuotationService;
use Core\Request;
use Core\Response;
use Core\View;

final class WorkAcceptanceFormController
{
	public function __construct(
		private readonly DocumentRepo $documentRepo,
		private readonly QuotationService $quotationService,
		private readonly PdfService $pdfService,
		private readonly View $view,
	){}

	public function index(Request $request): Response
	{
		$documents = $this->documentRepo->findByType('quotation');
		$data = [
			'documents' => $documents
		];

		if($request->isHtmx()){
			return Response::html($this->view->renderPartial('backoffice/work-acceptance-form/index', $data));
		}

		return Response::html($this->view->renderPage('backoffice/work-acceptance-form/index', $data));
	}

	public function show(Request $request, string $id): Response
	{
		$details = $this->quotationService->getDetails((int) $id);

		if($request->isHtmx()){
			return Response::html($this->view->renderPartial('backoffice/work-acceptance-form/show', $details));
		}

		return Response::html($this->view->renderPage('backoffice/work-acceptance-form/show', $details));
	}

	public function streamPdf(Request $request, string $id): Response
	{
		$details = $this->quotationService->getDetails((int) $id);
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
		$pdfHtml = $this->view->renderPage('backoffice/work-acceptance-form/_pdf', $data);

		$this->pdfService->generateFromHtml($pdfHtml, "{$document_no}_work_acceptance_form.pdf", true, $pdfHeader, $pdfFooter);

		return new Response('', 200, ['Content-Type' => 'application/pdf']);
	}
}