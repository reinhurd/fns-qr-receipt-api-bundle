<?php

namespace Reinhurd\FnsQrReceiptApiBundle\Service;

use Reinhurd\FnsQrReceiptApiBundle\Service\Exception\CurlRequestException;

class CurlRequestService
{
    private $proxySettings = '127.0.0.1:1337';
    private $isProxyEnabled = false;

    public function curlRequest(string $body, array $header, string $apiUrl)
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
        curl_close($ch);

        if (!empty($error)) {
            throw new CurlRequestException($error);
        }

        return $response;
    }
}