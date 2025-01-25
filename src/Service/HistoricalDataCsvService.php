<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\HistoryItemDTO;
use League\Csv\CannotInsertRecord;
use League\Csv\Exception;
use League\Csv\Writer;

class HistoricalDataCsvService
{
    public function createFromString(string $string = ''): Writer
    {
        return Writer::createFromString($string);
    }

    /**
     * @throws CannotInsertRecord
     * @throws Exception
     */
    public function addHeaders(Writer $csv): Writer
    {
        $csv->insertOne(['Date', 'Open', 'High', 'Low', 'Close', 'Volume']);

        return $csv;
    }

    /**
     * @param Writer $csv
     * @param HistoryItemDTO[] $historicalData
     *
     * @return Writer
     *
     * @throws CannotInsertRecord
     * @throws Exception
     */
    public function addHistoricalData(Writer $csv, array $historicalData): Writer
    {
        foreach ($historicalData as $historyItemDTO) {
            $csv->insertOne([
                $historyItemDTO->date,
                $historyItemDTO->open,
                $historyItemDTO->high,
                $historyItemDTO->low,
                $historyItemDTO->close,
                $historyItemDTO->volume,
            ]);
        }

        return $csv;
    }
}