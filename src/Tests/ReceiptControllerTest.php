<?php

namespace Reinhurd\FnsQrReceiptApiBundle\Test;

use PHPUnit\Framework\TestCase;
use Reinhurd\FnsQrReceiptApiBundle\Controller\ReceiptController;
use Reinhurd\FnsQrReceiptApiBundle\Service\ReceiptTaxApiService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ReceiptControllerTest extends TestCase
{
    private $controller;
    private $taxApiService;

    public function setUp(): void
    {
        parent::setUp();
        $this->taxApiService = $this->createMock(ReceiptTaxApiService::class);
        $this->controller = new ReceiptController($this->taxApiService);
    }

    public function testInfo()
    {
        $expectedResponceJson = json_decode('{"a":1,"b":2,"c":3,"d":4,"e":5}', true);
        $testString = 'testString';
        $requestMock = $this->createMock(Request::class);
        $requestMock->method('get')->willReturn($testString);
        $this->taxApiService->method('getReceiptInfo')->willReturn($expectedResponceJson);

        $result = $this->controller->info($requestMock);
        $this->assertEquals(new JsonResponse($expectedResponceJson), $result);
    }
}

