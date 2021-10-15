<?php

namespace Reinhurd\FnsQrReceiptApiBundle\Service;

use Reinhurd\FnsQrReceiptApiBundle\Service\Exception\InvalidReceiptRequestException;
use Reinhurd\FnsQrReceiptApiBundle\Service\helpers\XMLHelper;
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
    private $curlRequestService;
    private $httpClient;
    private $parameterBag;
    private $xmlHelper;

    public function __construct(
        CurlRequestService $curlRequestService,
        HttpClientInterface $httpClient,
        ParameterBagInterface $parameterBag,
        XMLHelper $xmlHelper
    ) {
        $this->curlRequestService = $curlRequestService;
        //todo replace curl with HttpClientInterface https://symfony.com/doc/current/http_client.html
        $this->httpClient = $httpClient;
        $this->parameterBag = $parameterBag;
        $this->xmlHelper = $xmlHelper;
        $this->apiAuthUrl = $this->parameterBag->get('reinhurd_fns_qr_receipt_api.api.auth_url');
        $this->apiRequestUrl = $this->parameterBag->get('reinhurd_fns_qr_receipt_api.api.request_url');
        $this->apiMasterToken = $this->parameterBag->get('reinhurd_fns_qr_receipt_api.master_token');
    }

    /**
     * request data example
     * 'sum' => '12500',
     * 'date' => '2020-04-23T12:08:00',
     * 'fn' => '9287440300077658',
     * 'fiscalDocumentId' => '166865',
     * 'fiscalSign' => '4264393268'
     *
     * @param array $receiptData
     * @return array
     */
    public function getReceiptInfo(array $receiptData): array
    {
        if (empty($receiptData)) {
            throw new InvalidReceiptRequestException();
        }

        $headerForToken = ["Content-Type: text/xml"];
        $responseWithTempToken = $this
            ->curlRequestService
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
            ->curlRequestService
            ->curlRequest(
                $this->getBodyWithTokenRequestReceipt($receiptData),
                $headerWithToken,
                $this->apiRequestUrl
            );

        $messageId = $this->xmlHelper->parseXMLByTag($responseWithMessageId, self::XML_TAG_MESSAGE_ID);

        $bodyForRequestByMessageId = $this->getBodyWithMessageIdFinalRequest($messageId);

        //todo make private function about this
        for ($i = 0; $i < self::LIMIT_LOOP_RUNS_FOR_ONE_REQUEST; $i++) {
            $responseAboutReceipt = $this
                ->curlRequestService
                ->curlRequest(
                    $bodyForRequestByMessageId,
                    $headerWithToken,
                    $this->apiRequestUrl
                );
            if (!$this->checkProcessingStatus($responseAboutReceipt)) {
                sleep(self::LIMIT_WAIT_TIME_BETWEEN_LOOP_RUN_SECONDS);
            } else {
                break;
            }
        }

        $responceWithReceiptInfo = $this->xmlHelper->parseXMLByTag($responseAboutReceipt, self::XML_TAG_TICKET);

        return json_decode($responceWithReceiptInfo, true);
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

    private function getBodyWithTokenRequestReceipt(array $receiptData): string
    {
        return "<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\"
                 xmlns:ns=\"urn://x-artefacts-gnivc-ru/inplat/servin/OpenApiAsyncMessageConsumerService/types/1.0\">
                <soapenv:Header/>
                <soapenv:Body>
                    <ns:SendMessageRequest>
                        <ns:Message>
                            <tns:GetTicketRequest xmlns:tns=\"urn://x-artefacts-gnivc-ru/ais3/kkt/KktTicketService/types/1.0\">
                                <tns:GetTicketInfo>
                                    <tns:Sum>{$receiptData['sum']}</tns:Sum>
                                    <tns:Date>{$receiptData['date']}</tns:Date>
                                    <tns:Fn>{$receiptData['fn']}</tns:Fn>
                                    <tns:TypeOperation>1</tns:TypeOperation>
                                    <tns:FiscalDocumentId>{$receiptData['fiscalDocumentId']}</tns:FiscalDocumentId>
                                    <tns:FiscalSign>{$receiptData['fiscalSign']}</tns:FiscalSign>
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
