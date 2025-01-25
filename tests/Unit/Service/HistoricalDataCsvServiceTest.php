<?php

namespace App\Tests\Unit\Service;

use App\DTO\HistoryItemDTO;
use App\Service\HistoricalDataCsvService;
use League\Csv\Writer;
use PHPUnit\Framework\TestCase;

use function PHPUnit\Framework\assertInstanceOf;
use function PHPUnit\Framework\assertSame;

class HistoricalDataCsvServiceTest extends TestCase
{

    public function testCreateFromString()
    {
        $service = new HistoricalDataCsvService();
        assertInstanceOf(Writer::class, $service->createFromString());
    }

    public function testAddHeaders()
    {
        $csv = $this->createMock(Writer::class);
        $csv->expects($this->once())->method('insertOne')->with(['Date', 'Open', 'High', 'Low', 'Close', 'Volume']);

        $service = new HistoricalDataCsvService();
        assertSame($csv, $service->addHeaders($csv));
    }

    public function testAddHistoricalData()
    {
        $historicalData = [
            new HistoryItemDTO('symbol', '2025-01-01', 1, 1, 1, 1, 1),
            new HistoryItemDTO('symbol', '2025-01-01', 1, 1, 1, 1, 1),
        ];
        $csv = $this->createMock(Writer::class);
        $csv->expects($this->exactly(2))->method('insertOne');
        $service = new HistoricalDataCsvService();
        assertSame($csv, $service->addHistoricalData($csv, $historicalData));
    }
}
