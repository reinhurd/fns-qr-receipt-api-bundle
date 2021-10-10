<?php

namespace Reinhurd\FnsQrReceiptApiBundle\Controller;

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
        //todo create mapper
        $receiptRequestData = [];

        $receiptRequestData['sum'] = $request->get('sum');
        $receiptRequestData['date'] = $request->get('date');
        $receiptRequestData['fn'] = $request->get('fn');
        $receiptRequestData['fiscalDocumentId'] = $request->get('fiscalDocumentId');
        $receiptRequestData['fiscalSign'] = $request->get('fiscalSign');

        $infoAboutReceipt = $this->taxApiService->getReceiptInfo($receiptRequestData);

        return new JsonResponse($infoAboutReceipt);
    }
}