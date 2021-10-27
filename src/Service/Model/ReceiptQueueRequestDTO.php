<?php

namespace Reinhurd\FnsQrReceiptApiBundle\Service\Model;

use Reinhurd\FnsQrReceiptApiBundle\Service\Exception\InvalidRequestException;

class ReceiptQueueRequestDTO
{
    private $ReceiptRequestDTO;
    private $messageId;

    public function setReceiptRequestDTO(ReceiptRequestDTO $ReceiptRequestDTO): void
    {
        $this->ReceiptRequestDTO = $ReceiptRequestDTO;
    }

    public function setMessageId(string $messageId): void
    {
        if (empty($messageId)) {
            throw new InvalidRequestException('MessageId is empty');
        }
        $this->messageId = $messageId;
    }

    public function getReceiptRequestDTO(): ReceiptRequestDTO
    {
        return $this->ReceiptRequestDTO;
    }

    public function getMessageId(): string
    {
        return $this->messageId;
    }
}
