<?php

namespace Reinhurd\FnsQrReceiptApiBundle\Test;

use PHPUnit\Framework\TestCase;
use Reinhurd\FnsQrReceiptApiBundle\Service\helpers\XMLHelper;
use Reinhurd\FnsQrReceiptApiBundle\Service\HttpClientRequestService;
use Reinhurd\FnsQrReceiptApiBundle\Service\Model\ReceiptRequestDTO;
use Reinhurd\FnsQrReceiptApiBundle\Service\ReceiptTaxApiService;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ReceiptTaxApiServiceTest extends TestCase
{
    private const STUB_DATA = '12345';
    private $service;
    private $httpClientRequestService;
    private $parameterBag;
    private $xmlHelper;

    public function setUp(): void
    {
        parent::setUp();

        $this->httpClientRequestService = $this->createMock(HttpClientRequestService::class);
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);
        $this->xmlHelper = $this->createMock(XMLHelper::class);
        $this->parameterBag->expects(self::any())->method('get')->willReturn(self::STUB_DATA);

        $this->service = new ReceiptTaxApiService(
            $this->httpClientRequestService,
            $this->parameterBag,
            $this->xmlHelper
        );
    }

    public function testGetReceiptInfo()
    {
        $receiptMockDto = new ReceiptRequestDTO();
        $receiptMockDto->setFiscalSign(self::STUB_DATA);
        $receiptMockDto->setFiscalDocumentId(self::STUB_DATA);
        $receiptMockDto->setDate(self::STUB_DATA);
        $receiptMockDto->setSum(self::STUB_DATA);
        $receiptMockDto->setFiscalNumber(self::STUB_DATA);

        $expectedResponceJson = '{"a":1,"b":2,"c":3,"d":4,"e":5}';
        $curlResponseMock = 'testString';

        $this
            ->httpClientRequestService
            ->expects(self::atLeast(3))
            ->method('curlRequest')
            ->willReturn($curlResponseMock);

        $this
            ->xmlHelper
            ->expects(self::atLeast(3))
            ->method('parseXMLByTag')
            ->willReturn($expectedResponceJson);

        $result = $this->service->getReceiptInfo($receiptMockDto);

        $this->assertEquals(json_decode($expectedResponceJson, true), $result);
    }
}