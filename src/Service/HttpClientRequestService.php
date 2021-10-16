<?php

namespace Reinhurd\FnsQrReceiptApiBundle\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class HttpClientRequestService
{
    private $proxySettings = '127.0.0.1:1337';
    private $isProxyEnabled = false;
    private $httpClient;

    public function __construct(
        HttpClientInterface $httpClient
    ) {
        $this->httpClient = $httpClient;
    }

    public function curlRequest(string $body, array $header, string $apiUrl): string
    {
        $extraParam = [];

        if ($this->isProxyEnabled) {
            $extraParam = array_merge(
                $extraParam,
                [
                    CURLOPT_PROXY => $this->proxySettings,
                    CURLOPT_PROXYTYPE => CURLPROXY_SOCKS5,
                    CURLOPT_PROXYTYPE => 7,
                ]
            );
        }

        $extraParam = array_merge(
            $extraParam,
            [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS =>$body,
                CURLOPT_HTTPHEADER => $header,
            ]
        );

        $response = $this->httpClient->request('POST', $apiUrl, [
            'max_redirects' => 10,
            'extra' => [
                'curl' => $extraParam,
            ],
        ]);

        return $response->getContent();
    }
}