<?php

namespace App\Tests\Unit\ApiClient\HistoricalData;

use App\ApiClient\HistoricalData\YahooFinanceApiClient;
use App\DTO\HistoryItemDTO;
use App\DTO\StockRequestDTO;
use App\Transformer\HistoryItemTransformer;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

use function PHPUnit\Framework\assertContainsOnlyInstancesOf;
use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertSame;

class YahooFinanceApiClientTest extends TestCase
{

    public function testFetchHistoricalData_success()
    {
        $stockRequestDTO = new StockRequestDTO('MSTR', '2025-01-05', '2025-01-07', 'test@test.test');

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('toArray')->willReturn($this->getSuccessfulResponseArray());
        $response->method('getStatusCode')->willReturn(Response::HTTP_OK);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->once())->method('request')->with(
            Request::METHOD_GET, 'https://yh-finance.p.rapidapi.com/stock/v3/get-chart', [
                'headers' => [
                    'X-RapidAPI-Key' => 'test_api_key',
                    'X-RapidAPI-Host' => 'test_api_host',
                ],
                'query' => [
                    'symbol' => 'MSTR',
                    'interval' => '1d',
                    'period1' => strtotime('2025-01-05'),
                    'period2' => strtotime('2025-01-07'),
                    'events' => 'history',
                ],
            ]
        )->willReturn($response);

        $historyDTOs = [
            new HistoryItemDTO(
                'MSTR', '2025-01-05', 123, 789, 100, 456, 1000000
            ),
            new HistoryItemDTO(
                'MSTR', '2025-01-06', 321, 987, 200, 654, 2000000
            ),
        ];

        $transformer = $this->createMock(HistoryItemTransformer::class);
        $transformer->expects($this->once())->method('transform')->with(
            $this->getSuccessfulResponseArray()
        )->willReturn($historyDTOs);

        $apiClient = new YahooFinanceApiClient(
            $httpClient, $this->createMock(LoggerInterface::class), 'test_api_key', 'test_api_host'
        );
        $history = $apiClient->fetchHistoricalData(
            $stockRequestDTO->companySymbol,
            $stockRequestDTO->getStartDateImmutable(),
            $stockRequestDTO->getEndDateImmutable(),
            $transformer
        );

        assertCount(2, $history);
        assertContainsOnlyInstancesOf(HistoryItemDTO::class, $history);
        assertSame($historyDTOs[0], $history[0]);
        assertSame($historyDTOs[1], $history[1]);
    }

    protected function getSuccessfulResponseArray(): array
    {
        return [
            'chart' => [
                'result' => [
                    [
                        'meta' => [
                            'symbol' => 'MSTR',
                        ],
                        'timestamp' => [1736031600, 1736118000],
                        'indicators' => [
                            'quote' => [
                                [
                                    'open' => [123, 321],
                                    'high' => [789, 987],
                                    'low' => [100, 200],
                                    'close' => [456, 654],
                                    'volume' => [1000000, 2000000],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
