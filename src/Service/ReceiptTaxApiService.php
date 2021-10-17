<?php

namespace Reinhurd\FnsQrReceiptApiBundle\Service;

use Reinhurd\FnsQrReceiptApiBundle\Service\Exception\InvalidReceiptRequestException;
use Reinhurd\FnsQrReceiptApiBundle\Service\helpers\XMLHelper;
use Reinhurd\FnsQrReceiptApiBundle\Service\Model\ReceiptRequestDTO;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ReceiptTaxApiService
{
    const LIMIT_LOOP_RUNS_FOR_ONE_REQUEST = 10;
    const LIMIT_WAIT_TIME_BETWEEN_LOOP_RUN_SECONDS = 5;
    const PROCESSING_STATUS = 'PROCESSING';
    const XML_TAG_TOKEN = 'Token';
    const XML_TAG_MESSAGE_ID = 'MessageId';
    const XML_TAG_MESSAGE = 'Message';
    const XML_TAG_CODE = 'Code';
    const XML_TAG_TICKET = 'Ticket';
    const XML_TAG_PROCESSING_STATUS = 'ProcessingStatus';

    private $apiAuthUrl;
    private $apiRequestUrl;
    private $apiMasterToken;
    private $httpClientRequestService;
    private $parameterBag;
    private $xmlHelper;

    public function __construct(
        HttpClientRequestService $httpClientRequestService,
        ParameterBagInterface $parameterBag,
        XMLHelper $xmlHelper
    ) {
        $this->httpClientRequestService = $httpClientRequestService;
        $this->parameterBag = $parameterBag;
        $this->xmlHelper = $xmlHelper;
        $this->apiAuthUrl = $this->parameterBag->get('reinhurd_fns_qr_receipt_api.api.auth_url');
        $this->apiRequestUrl = $this->parameterBag->get('reinhurd_fns_qr_receipt_api.api.request_url');
        $this->apiMasterToken = $this->parameterBag->get('reinhurd_fns_qr_receipt_api.master_token');
    }

    public function getReceiptInfo(ReceiptRequestDTO $receiptData): array
    {
        $headerForToken = ["Content-Type: text/xml"];
        $responseWithTempToken = $this
            ->httpClientRequestService
            ->curlRequest(
                $this->getBodyTemporaryToken(),
                $headerForToken,
                $this->apiAuthUrl
            );
        $tempToken = $this->xmlHelper->parseXMLByTag($responseWithTempToken, self::XML_TAG_TOKEN);

        $headerWithToken =[
            "FNS-OpenApi-Token: {$tempToken}",
            "FNS-OpenApi-UserToken: {$this->apiMasterToken}",
            "Content-Type: text/xml"
        ];

        $responseWithMessageId = $this
            ->httpClientRequestService
            ->curlRequest(
                $this->getBodyWithTokenRequestReceipt($receiptData),
                $headerWithToken,
                $this->apiRequestUrl
            );

        $messageId = $this->xmlHelper->parseXMLByTag($responseWithMessageId, self::XML_TAG_MESSAGE_ID);

        $bodyForRequestByMessageId = $this->getBodyWithMessageIdFinalRequest($messageId);

        $responseAboutReceipt = $this->loopRequestAboutReceipt($bodyForRequestByMessageId, $headerWithToken);
        $responceWithReceiptInfo = $this->xmlHelper->parseXMLByTag($responseAboutReceipt, self::XML_TAG_TICKET);

        return json_decode($responceWithReceiptInfo, true);
    }

    private function loopRequestAboutReceipt(string $body, array $header): string
    {
        for ($i = 0; $i < self::LIMIT_LOOP_RUNS_FOR_ONE_REQUEST; $i++) {
            $responseAboutReceipt = $this
                ->httpClientRequestService
                ->curlRequest(
                    $body,
                    $header,
                    $this->apiRequestUrl
                );
            if (!$this->checkProcessingStatus($responseAboutReceipt)) {
                sleep(self::LIMIT_WAIT_TIME_BETWEEN_LOOP_RUN_SECONDS);
            } else {
                break;
            }
        }

        return $responseAboutReceipt;
    }

    private function checkProcessingStatus(string $answer): bool
    {
        $processingStatus = $this->xmlHelper->parseXMLByTag($answer, self::XML_TAG_PROCESSING_STATUS);
        if ($processingStatus === self::PROCESSING_STATUS) {
            return false;
        }

        return true;
    }

    private function getBodyTemporaryToken(): string
    {
        return "
            <soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:ns=\"urn://x-artefacts-gnivc-ru/inplat/servin/OpenApiMessageConsumerService/types/1.0\">
            <soapenv:Header/>
            <soapenv:Body>
            <ns:GetMessageRequest>
            <ns:Message>\n<tns:AuthRequest xmlns:tns=\"urn://x-artefacts-gnivc-ru/ais3/kkt/AuthService/types/1.0\">
            <tns:AuthAppInfo>
            <tns:MasterToken>{$this->apiMasterToken}</tns:MasterToken>
            </tns:AuthAppInfo>
            </tns:AuthRequest>
            </ns:Message>
            </ns:GetMessageRequest>
            </soapenv:Body>
            </soapenv:Envelope>
        ";
    }

    private function getBodyWithTokenRequestReceipt(ReceiptRequestDTO $receiptData): string
    {
        return "<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\"
                 xmlns:ns=\"urn://x-artefacts-gnivc-ru/inplat/servin/OpenApiAsyncMessageConsumerService/types/1.0\">
                <soapenv:Header/>
                <soapenv:Body>
                    <ns:SendMessageRequest>
                        <ns:Message>
                            <tns:GetTicketRequest xmlns:tns=\"urn://x-artefacts-gnivc-ru/ais3/kkt/KktTicketService/types/1.0\">
                                <tns:GetTicketInfo>
                                    <tns:Sum>{$receiptData->getSum()}</tns:Sum>
                                    <tns:Date>{$receiptData->getDate()}</tns:Date>
                                    <tns:Fn>{$receiptData->getFiscalNumber()}</tns:Fn>
                                    <tns:TypeOperation>1</tns:TypeOperation>
                                    <tns:FiscalDocumentId>{$receiptData->getFiscalDocumentId()}</tns:FiscalDocumentId>
                                    <tns:FiscalSign>{$receiptData->getFiscalSign()}</tns:FiscalSign>
                                </tns:GetTicketInfo>
                            </tns:GetTicketRequest>
                        </ns:Message>
                    </ns:SendMessageRequest>
                </soapenv:Body>
            </soapenv:Envelope>";
    }

    private function getBodyWithMessageIdFinalRequest(string $messageId): string
    {
        return "
            <soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:ns=\"urn://x-artefacts-gnivc-ru/inplat/servin/OpenApiAsyncMessageConsumerService/types/1.0\">
            <soapenv:Header/>
            <soapenv:Body>
              <ns:GetMessageRequest>
                 <ns:MessageId>{$messageId}</ns:MessageId>
              </ns:GetMessageRequest>
            </soapenv:Body>
            </soapenv:Envelope>
        ";
    }
}
