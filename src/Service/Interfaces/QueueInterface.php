<?php

namespace Reinhurd\FnsQrReceiptApiBundle\Service\Interfaces;

use Reinhurd\FnsQrReceiptApiBundle\Service\Model\ReceiptQueueRequestDTO;

interface QueueInterface
{
    public function saveNotProcessingRequest(ReceiptQueueRequestDTO $request): void;

    public function readLastNotProcessingRequest(): ReceiptQueueRequestDTO;
}
