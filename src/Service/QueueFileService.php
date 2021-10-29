<?php

namespace Reinhurd\FnsQrReceiptApiBundle\Service;

use Reinhurd\FnsQrReceiptApiBundle\Service\Exception\InvalidFileException;
use Reinhurd\FnsQrReceiptApiBundle\Service\Interfaces\QueueInterface;
use Reinhurd\FnsQrReceiptApiBundle\Service\Model\ReceiptQueueRequestDTO;

//todo make connection to DB!
class QueueFileService implements QueueInterface
{
    private const SEPARATOR_CSV = ';';
    private const DEFAULT_FILE = 'Unprocessed_entities.csv';

    private $fileStream;
    public function __construct(string $filename = null)
    {
        if (empty($filename)) {
            $filename = self::DEFAULT_FILE;
        }
        //todo move file operation to file helper
        $this->filename = $filename;
        $this->fileStream = fopen($this->filename, 'a');
        if ($this->fileStream === false) {
            throw new InvalidFileException();
        }
    }

    public function saveNotProcessingRequest(ReceiptQueueRequestDTO $request): void
    {
        $jsonToSave = json_encode($request);
        $result = fputcsv($this->fileStream, [$jsonToSave], self::SEPARATOR_CSV);
        if ($result === false) {
            throw new InvalidFileException();
        }
     }

    public function readLastNotProcessingRequest(): ReceiptQueueRequestDTO
    {
        //todo add helper and add read all lines
        $csv = array_map('str_getcsv', file($this->fileStream));
        $lastRowIndex = count($csv) - 1;

        return $csv[$lastRowIndex];
    }

    public function __destruct()
    {
        fclose($this->fileStream);
    }
}
