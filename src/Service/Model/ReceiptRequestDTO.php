<?php

namespace Reinhurd\FnsQrReceiptApiBundle\Service\Model;

use Reinhurd\FnsQrReceiptApiBundle\Service\Exception\InvalidReceiptRequestException;

/**
 * Class ReceiptRequestDTO
 * @package Reinhurd\FnsQrReceiptApiBundle\Service\Model
 *  request data example
 * 'sum' => '12500',
 * 'date' => '2020-04-23T12:08:00',
 * 'fn' => '9287440300077658',
 * 'fiscalDocumentId' => '166865',
 * 'fiscalSign' => '4264393268'
 */
class ReceiptRequestDTO
{
    private $sum;
    private $date;
    private $fiscalNumber;
    private $fiscalDocumentId;
    private $fiscalSign;

    public function setSum(string $sum): void
    {
        if (empty($sum)) {
            throw new InvalidReceiptRequestException('Sum is empty');
        }
        $this->sum = $sum;
    }

    public function setDate(string $date): void
    {
        if (empty($date)) {
            throw new InvalidReceiptRequestException('Date is empty');
        }
        $this->date = $date;
    }

    public function setFiscalNumber(string $fiscalNumber): void
    {
        if (empty($fiscalNumber)) {
            throw new InvalidReceiptRequestException('fiscalNumber is empty');
        }
        $this->fiscalNumber = $fiscalNumber;
    }

    public function setFiscalSign(string $fiscalSign): void
    {
        if (empty($fiscalSign)) {
            throw new InvalidReceiptRequestException('fiscalSign is empty');
        }
        $this->fiscalSign = $fiscalSign;
    }

    public function setFiscalDocumentId(string $fiscalDocumentId): void
    {
        if (empty($fiscalDocumentId)) {
            throw new InvalidReceiptRequestException('fiscalDocumentId is empty');
        }
        $this->fiscalDocumentId = $fiscalDocumentId;
    }

    public function getDate(): string
    {
        return $this->date;
    }

    public function getFiscalDocumentId(): string
    {
        return $this->fiscalDocumentId;
    }

    public function getFiscalSign(): string
    {
        return $this->fiscalSign;
    }

    public function getFiscalNumber(): string
    {
        return $this->fiscalNumber;
    }

    public function getSum(): string
    {
        return $this->sum;
    }
}
