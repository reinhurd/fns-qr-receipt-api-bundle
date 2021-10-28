<?php

namespace Reinhurd\FnsQrReceiptApiBundle\Service\Model;

class ReceiptQueueRequestDTO
{
    private $ReceiptRequestDTO;
    private $messageId;

    public function __construct(
        ReceiptRequestDTO $ReceiptRequestDTO,
        string $messageId
    ) {
        $this->ReceiptRequestDTO = $ReceiptRequestDTO;
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
