<?php

namespace Reinhurd\FnsQrReceiptApiBundle\Test;

use PHPUnit\Framework\TestCase;
use Reinhurd\FnsQrReceiptApiBundle\Service\HttpClientRequestService;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class HttpClientRequestServiceTest extends TestCase
{
    private $httpClient;
    private $parameterBag;
    private $service;

    public function setUp(): void
    {
        parent::setUp();
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);
        $this->parameterBag->expects(self::any())->method('get')->willReturn(true);
        $this->service = new HttpClientRequestService($this->httpClient, $this->parameterBag);
    }

    public function testCurlRequest()
    {
        $expectedString = 'testString';
        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('getContent')->willReturn($expectedString);
        $this->httpClient->method('request')->willReturn($responseMock);

        $result = $this->service->curlRequest('test', [], 'test');
        $this->assertEquals($expectedString, $result);
    }
}
