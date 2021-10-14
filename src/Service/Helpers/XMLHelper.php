<?php

namespace Reinhurd\FnsQrReceiptApiBundle\Service\helpers;

use Reinhurd\FnsQrReceiptApiBundle\Service\Exception\InvalidResponseDataException;
use Reinhurd\FnsQrReceiptApiBundle\Service\ReceiptTaxApiService;

class XMLHelper
{
    public function parseXMLByTag(string $xml, string $tag): string
    {
        $dom = new DOMDocument();
        $dom->loadXML($xml);

        $element = $dom->getElementsByTagName($tag)[0];
        if ($element === null) {
            //get error message
            $messageElement = $dom->getElementsByTagName(ReceiptTaxApiService::XML_TAG_MESSAGE)[0];

            throw new InvalidResponseDataException($messageElement);
        }

        return $element->nodeValue;
    }

}
