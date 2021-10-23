<?php

namespace Reinhurd\FnsQrReceiptApiBundle\Service\Helpers;

use DOMDocument;
use Reinhurd\FnsQrReceiptApiBundle\Service\Exception\InvalidResponseDataException;
use Reinhurd\FnsQrReceiptApiBundle\Service\ReceiptTaxApiService;

class XMLHelper
{
    private $domDocument;

    public function __construct(DOMDocument $domDocument)
    {
        $this->domDocument = $domDocument;
    }

    public function parseXMLByTag(string $xml, string $tag): string
    {
        $this->domDocument->loadXML($xml);

        $element = $this->domDocument->getElementsByTagName($tag)[0];
        if ($element === null) {
            //get error message
            $messageElement = $this->domDocument->getElementsByTagName(ReceiptTaxApiService::XML_TAG_MESSAGE)[0];

            throw new InvalidResponseDataException($messageElement);
        }

        return $element->nodeValue;
    }

}
