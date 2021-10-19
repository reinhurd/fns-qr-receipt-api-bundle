<?php

namespace Reinhurd\FnsQrReceiptApiBundle\Test;

use PHPUnit\Framework\TestCase;
use Reinhurd\FnsQrReceiptApiBundle\Service\helpers\XMLHelper;
use Reinhurd\FnsQrReceiptApiBundle\Service\HttpClientRequestService;
use Reinhurd\FnsQrReceiptApiBundle\Service\ReceiptTaxApiService;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ReceiptTaxApiServiceTest extends TestCase
{
    private const TEST_SETTING_MOCK = '12345';
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
        $this->parameterBag->expects(self::any())->method('get')->willReturn(self::TEST_SETTING_MOCK);

        $this->service = new ReceiptTaxApiService(
            $this->httpClientRequestService,
            $this->parameterBag,
            $this->xmlHelper
        );
    }

    public function testGetReceiptInfo()
    {
        $expectedResponceJson = '{"a":1,"b":2,"c":3,"d":4,"e":5}';
        //todo finalize test method
    }
}