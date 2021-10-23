<?php

namespace Reinhurd\FnsQrReceiptApiBundle\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class HttpClientRequestService
{
    private const DEFAULT_REQUEST_METHOD = 'POST';
    private $httpClient;
    private $isProxyEnabled;
    private $parameterBag;
    private $proxySettings;

    public function __construct(
        HttpClientInterface $httpClient,
        ParameterBagInterface $parameterBag
    ) {
        $this->httpClient = $httpClient;
        $this->parameterBag = $parameterBag;
        $this->isProxyEnabled = $this->parameterBag->get('reinhurd_fns_qr_receipt_api.proxy.enable');
        if ($this->isProxyEnabled) {
            $this->proxySettings = $this->parameterBag->get('reinhurd_fns_qr_receipt_api.proxy.settings');
        }
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
                CURLOPT_CUSTOMREQUEST => self::DEFAULT_REQUEST_METHOD,
                CURLOPT_POSTFIELDS =>$body,
                CURLOPT_HTTPHEADER => $header,
            ]
        );

        $response = $this->httpClient->request(self::DEFAULT_REQUEST_METHOD, $apiUrl, [
            'max_redirects' => 10,
            'extra' => [
                'curl' => $extraParam,
            ],
        ]);

        return $response->getContent();
    }
}