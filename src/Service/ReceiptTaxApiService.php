<?php

namespace Reinhurd\FnsQrReceiptApiBundle\Service;

use Reinhurd\FnsQrReceiptApiBundle\Service\Exception\InvalidReceiptRequestException;
use Reinhurd\FnsQrReceiptApiBundle\Service\Exception\InvalidResponseDataException;

class ReceiptTaxApiService
{
    private $apiAuthUrl = 'https://openapi.nalog.ru:8090/open-api/AuthService/0.1?wsdl';
    private $apiMasterToken = '/*YOUR MASTER TOKEN HERE*/';
    private $apiRequestUrl = 'https://openapi.nalog.ru:8090/open-api/ais3/KktService/0.1?wsdl';
    private $curlRequestService;

    public function __construct(CurlRequestService $curlRequestService)
    {
        $this->curlRequestService = $curlRequestService;
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
        $bodyForToken = "
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

        $responseWithTempToken = $this->curlRequestService->curlRequest($bodyForToken, $headerForToken, $this->apiAuthUrl);

        $dom = new DOMDocument();
        $dom->loadXML($responseWithTempToken);
        foreach($dom->getElementsByTagName('Token') as $element){
            $tempToken = $element->nodeValue;
        }
        if (empty($tempToken)) {
            throw new InvalidResponseDataException();
        }

        $bodyForRequestAboutReceipt = "<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\"
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

        $headerWithToken =[
            "FNS-OpenApi-Token: {$tempToken}",
            "FNS-OpenApi-UserToken: {$this->apiMasterToken}",
            "Content-Type: text/xml"
        ];

        $responseWithMessageId = $this->curlRequestService->curlRequest($bodyForRequestAboutReceipt, $headerWithToken, $this->apiRequestUrl);

        $dom = new DOMDocument();
        $dom->loadXML($responseWithMessageId);
        foreach($dom->getElementsByTagName('MessageId') as $element){
            $messageId = $element->nodeValue;
        }
        if (empty($messageId)) {
            throw new InvalidResponseDataException();
        }

        $bodyForRequestByMessageId = "
            <soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:ns=\"urn://x-artefacts-gnivc-ru/inplat/servin/OpenApiAsyncMessageConsumerService/types/1.0\">
            <soapenv:Header/>
            <soapenv:Body>
              <ns:GetMessageRequest>
                 <ns:MessageId>{$messageId}</ns:MessageId>
              </ns:GetMessageRequest>
            </soapenv:Body>
            </soapenv:Envelope>
        ";

        $responseAboutReceipt = $this->curlRequestService->curlRequest($bodyForRequestByMessageId, $headerWithToken, $this->apiRequestUrl);

        $dom = new DOMDocument();
        $dom->loadXML($responseAboutReceipt);

        foreach($dom->getElementsByTagName('Code') as $element){
            $code = $element->nodeValue;
        }
        foreach($dom->getElementsByTagName('Ticket') as $element){
            $message = $element->nodeValue;
        }
        //todo add queue when request is still processing

        return json_decode($message, true);
    }
}
