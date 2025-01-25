<?php

namespace App\Tests\Unit\Service;

use App\ApiClient\HistoricalDataApiClientInterface;
use App\DTO\StockRequestDTO;
use App\Service\HistoricalDataService;
use App\Transformer\HistoryItemTransformer;
use PHPUnit\Framework\TestCase;

use function PHPUnit\Framework\assertSame;

class HistoricalDataServiceTest extends TestCase
{

    public function testGetHistoricalData()
    {
        $response = ['thisShouldBeAHistoryItemDTO'];

        $stockRequestDTO = new StockRequestDTO('symbol', '2025-01-01', '2025-01-02', 'test@test.test');
        $transformer = $this->createMock(HistoryItemTransformer::class);

        $apiClient = $this->createMock(HistoricalDataApiClientInterface::class);
        $apiClient->expects($this->once())->method('fetchHistoricalData')->with($stockRequestDTO, $transformer)->willReturn($response);
        $service = new HistoricalDataService($apiClient, $transformer);
        $result = $service->getHistoricalData($stockRequestDTO);
        assertSame($response, $result);
    }
}
