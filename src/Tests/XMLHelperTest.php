<?php

namespace Reinhurd\FnsQrReceiptApiBundle\Test;

use DOMDocument;
use DOMNodeList;
use PHPUnit\Framework\TestCase;
use Reinhurd\FnsQrReceiptApiBundle\Service\helpers\XMLHelper;

class XMLHelperTest extends TestCase
{
    private $service;
    private $dom;

    public function setUp(): void
    {
        parent::setUp();
        $this->dom = $this->createMock(DOMDocument::class);
        $this->service = new XMLHelper($this->dom);
    }

    public function testParseXMLByTag()
    {
        $xmlMock = 'teststring';
        $tagMock = 'tagString';
        $expectedResult = 'test';

        $this->dom->expects(self::atLeastOnce())->method('loadXML')->with($xmlMock);

        $nodeMock = $this->createMock(DOMNodeList::class);
        $nodeMock->nodeValue = $expectedResult;
        $arrayMock = [$nodeMock];

        $this->dom
            ->expects(self::atLeastOnce())
            ->method('getElementsByTagName')
            ->with($tagMock)
            ->willReturn($arrayMock);

        $result = $this->service->parseXMLByTag($xmlMock, $tagMock);

        $this->assertEquals($expectedResult, $result);
    }
}
