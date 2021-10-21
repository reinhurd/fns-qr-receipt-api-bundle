<?php

namespace Reinhurd\FnsQrReceiptApiBundle\Test;

use DOMDocument;
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
}