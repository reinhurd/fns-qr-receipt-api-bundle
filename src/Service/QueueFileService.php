<?php

namespace Reinhurd\FnsQrReceiptApiBundle\Service;

use Reinhurd\FnsQrReceiptApiBundle\Service\Exception\InvalidFileException;
use Reinhurd\FnsQrReceiptApiBundle\Service\Interfaces\QueueInterface;
use Reinhurd\FnsQrReceiptApiBundle\Service\Model\ReceiptQueueRequestDTO;
use Symfony\Component\Filesystem\Filesystem;

//todo make connection to DB!
class QueueFileService implements QueueInterface
{
    private const SEPARATOR_CSV = ';';
    private const DEFAULT_FILE = 'Unprocessed_entities.csv';

    private $fileStream;
    private $filesystem;

    public function __construct(
        Filesystem $filesystem,
        string $filename = null
    ) {
        $this->filesystem = $filesystem;

        if (empty($filename)) {
            $filename = self::DEFAULT_FILE;
        }
        if (!$this->filesystem->exists($filename) === false) {
            $this->filesystem->touch($filename);
        }
        $this->fileStream = fopen($filename, 'a');
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
