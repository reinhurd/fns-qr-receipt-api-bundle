<?php

namespace Reinhurd\FnsQrReceiptApiBundle;

class ReceiptTaxApiService
{
    private $apiAuthUrl = 'https://openapi.nalog.ru:8090/open-api/AuthService/0.1?wsdl';
    private $apiMasterToken = '/*YOUR MASTER TOKEN HERE*/';
    private $apiRequestUrl = 'https://openapi.nalog.ru:8090/open-api/ais3/KktService/0.1?wsdl';
    private $proxySettings = '127.0.0.1:1337';
    private $isProxyEnabled = false;

    private $receiptRequestData = [
        'sum' => '12500',
        'date' => '2020-04-23T12:08:00',
        'fn' => '9287440300077658',
        'fiscalDocumentId' => '166865',
        'fiscalSign' => '4264393268'
    ];

    public function getReceiptInfo(array $receiptData): array
    {
        if (empty($receiptData)) {
            $receiptData = $this->receiptRequestData;
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

        $responseWithTempToken = $this->curlRequest($bodyForToken, $headerForToken, $this->apiAuthUrl);

        $dom = new DOMDocument();
        $dom->loadXML($responseWithTempToken);
        foreach($dom->getElementsByTagName('Token') as $element){
            $tempToken = $element->nodeValue;
        }
        //todo add exception when tempToken is empty

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

        $responseWithMessageId = $this->curlRequest($bodyForRequestAboutReceipt, $headerWithToken, $this->apiRequestUrl);

        $dom = new DOMDocument();
        $dom->loadXML($responseWithMessageId);
        foreach($dom->getElementsByTagName('MessageId') as $element ){
            $messageId = $element->nodeValue;
        }
        //todo add exception when $messageId is empty

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

        $responseAboutReceipt = $this->curlRequest($bodyForRequestByMessageId, $headerWithToken, $this->apiRequestUrl);

        $dom = new DOMDocument();
        $dom->loadXML($responseAboutReceipt);

        foreach($dom->getElementsByTagName('Code') as $element ){
            $code = $element->nodeValue;
        }
        foreach($dom->getElementsByTagName('Ticket') as $element ){
            $message = $element->nodeValue;
        }

        return json_decode($message, true);
    }

    private function curlRequest(string $body, array $header, string $apiUrl)
    {
        $ch = curl_init();

        if ($this->isProxyEnabled) {
            curl_setopt($ch, CURLOPT_PROXY, $this->proxySettings);
            curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
            curl_setopt ($ch, CURLOPT_PROXYTYPE, 7);
        }

        curl_setopt_array(
            $ch,
            [
                CURLOPT_URL => $apiUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS =>$body,
                CURLOPT_HTTPHEADER => $header,
            ]
        );

        $response = curl_exec($ch);
        $error = curl_error($ch);
        //todo throw exception if error is not empty
        curl_close($ch);

        return $response;
    }
}
