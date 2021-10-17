<?php

namespace Reinhurd\FnsQrReceiptApiBundle\Controller;

use Reinhurd\FnsQrReceiptApiBundle\Service\Model\ReceiptRequestDTO;
use Reinhurd\FnsQrReceiptApiBundle\Service\ReceiptTaxApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ReceiptController extends AbstractController
{
    private $taxApiService;

    public function __construct(ReceiptTaxApiService $receiptTaxApiService)
    {
        $this->taxApiService = $receiptTaxApiService;
    }

    /**
     * @Route("/info", name="info", methods={"POST"})
     */
    public function info(Request $request): JsonResponse
    {
        $receiptRequestData = new ReceiptRequestDTO();

        $receiptRequestData->setSum($request->get('sum'));
        $receiptRequestData->setDate($request->get('date'));
        $receiptRequestData->setFiscalNumber($request->get('fn'));
        $receiptRequestData->setFiscalDocumentId($request->get('fiscalDocumentId'));
        $receiptRequestData->setFiscalSign($request->get('fiscalSign'));

        $infoAboutReceipt = $this->taxApiService->getReceiptInfo($receiptRequestData);

        return new JsonResponse($infoAboutReceipt);
    }
}