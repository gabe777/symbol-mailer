<?php

namespace App\Tests\Unit\Service;

use App\ApiClient\HistoricalDataApiClientInterface;
use App\Cache\HistoricalDataCache;
use App\Constant\DateFormat;
use App\DTO\HistoryItemDTO;
use App\DTO\StockRequestDTO;
use App\Service\HistoricalDataService;
use App\Transformer\HistoryItemTransformer;
use DateTimeImmutable;
use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertSame;

class HistoricalDataServiceTest extends TestCase
{

    public function testGetHistoricalData_inRequestCache()
    {
        $response = ['thisShouldBeAHistoryItemDTO'];

        $stockRequestDTO = new StockRequestDTO('symbol', '2025-01-01', '2025-01-02', 'test@test.test');
        $transformer = $this->createMock(HistoryItemTransformer::class);

        $apiClient = $this->createMock(HistoricalDataApiClientInterface::class);
        $apiClient->expects($this->never())->method('fetchHistoricalData');

        $historicalDataCache = $this->createMock(HistoricalDataCache::class);
        $historicalDataCache->expects($this->once())->method('getFromRequestCache')->with($stockRequestDTO)->willReturn(
            $response
        );
        $historicalDataCache->expects($this->never())->method('findMissingMonths');
        $historicalDataCache->expects($this->never())->method('getMonthlySegment');
        $historicalDataCache->expects($this->never())->method('storeMonthlySegment');
        $historicalDataCache->expects($this->never())->method('saveToRequestCache');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('error');

        $service = new HistoricalDataService(
            $apiClient, $transformer, $historicalDataCache, $logger
        );
        $result = $service->getHistoricalData($stockRequestDTO);
        assertSame($response, $result);
    }

    public function testGetHistoricalData_notInRequestCache_notInLongTermCache_currentMonth()
    {
        $testMonthStart = DateTimeImmutable::createFromFormat('', '')->setTime(0, 0)->modify('first day of this month');

        $apiResponse = [
            new HistoryItemDTO('symbol', $testMonthStart->format(DateFormat::ISO_DATE), 1, 1, 1, 1, 1),
            new HistoryItemDTO('symbol', $testMonthStart->modify('+ 3 days')->format(DateFormat::ISO_DATE), 1, 1, 1, 1, 1),
            new HistoryItemDTO('symbol', $testMonthStart->modify('+ 4 days')->format(DateFormat::ISO_DATE), 1, 1, 1, 1, 1),
            new HistoryItemDTO('symbol', $testMonthStart->modify('+ 13 days')->format(DateFormat::ISO_DATE), 1, 1, 1, 1, 1),
            new HistoryItemDTO('symbol', $testMonthStart->modify('+ 14 days')->format(DateFormat::ISO_DATE), 1, 1, 1, 1, 1),
            new HistoryItemDTO('symbol', $testMonthStart->modify('+ 15 days')->format(DateFormat::ISO_DATE), 1, 1, 1, 1, 1),
            new HistoryItemDTO('symbol', $testMonthStart->modify('last day of this month')->format(DateFormat::ISO_DATE), 1, 1, 1, 1, 1),
        ];

        $expectedResponse = [
            new HistoryItemDTO('symbol', $testMonthStart->modify('+ 3 days')->format(DateFormat::ISO_DATE), 1, 1, 1, 1, 1),
            new HistoryItemDTO('symbol', $testMonthStart->modify('+ 4 days')->format(DateFormat::ISO_DATE), 1, 1, 1, 1, 1),
            new HistoryItemDTO('symbol', $testMonthStart->modify('+ 13 days')->format(DateFormat::ISO_DATE), 1, 1, 1, 1, 1),
            new HistoryItemDTO('symbol', $testMonthStart->modify('+ 14 days')->format(DateFormat::ISO_DATE), 1, 1, 1, 1, 1),
        ];

        $stockRequestDTO = new StockRequestDTO('symbol', $testMonthStart->modify('+ 3 days')->format(DateFormat::ISO_DATE), $testMonthStart->modify('+ 14 days')->format(DateFormat::ISO_DATE), 'test@test.test');
        $transformer = $this->createMock(HistoryItemTransformer::class);

        $apiClient = $this->createMock(HistoricalDataApiClientInterface::class);
        $apiClient->expects($this->once())->method('fetchHistoricalData')->with(
            'symbol',
            $testMonthStart,
            $testMonthStart->modify('last day of this month')->setTime(0, 0)
        )->willReturn($apiResponse);

        $historicalDataCache = $this->createMock(HistoricalDataCache::class);
        $historicalDataCache->expects($this->once())->method('getFromRequestCache')->with($stockRequestDTO)->willReturn(
            null
        );
        $historicalDataCache->expects($this->once())->method('findMissingMonths')->with(
            'symbol', [$testMonthStart]
        )->willReturn([$testMonthStart]);
        $historicalDataCache->expects($this->once())->method('storeMonthlySegment')->with(
            'symbol',
            $testMonthStart,
            $apiResponse
        );
        $historicalDataCache->expects($this->once())->method('getMonthlySegment')->with(
            'symbol',
            $testMonthStart
        )->willReturn($apiResponse);
        $historicalDataCache->expects($this->once())->method('saveToRequestCache')->with(
            $stockRequestDTO,
            $expectedResponse
        );

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('error');

        $service = new HistoricalDataService(
            $apiClient, $transformer, $historicalDataCache, $logger
        );
        $result = $service->getHistoricalData($stockRequestDTO);
        assertEquals($expectedResponse, $result);
    }

    public function testGetHistoricalData_notInRequestCache_notInLongTermCache_multiMonth()
    {
        $testFirstMonthStart = DateTimeImmutable::createFromFormat(DateFormat::ISO_DATE, '2025-01-01')->setTime(0, 0);
        $testSecondMonthStart = DateTimeImmutable::createFromFormat(DateFormat::ISO_DATE, '2025-02-01')->setTime(0, 0);

        $apiResponse = [
            new HistoryItemDTO('symbol', '2025-01-01', 1, 1, 1, 1, 1),
            new HistoryItemDTO('symbol', '2025-01-04', 1, 1, 1, 1, 1),
            new HistoryItemDTO('symbol', '2025-01-16', 1, 1, 1, 1, 1),
            new HistoryItemDTO('symbol', '2025-01-31', 1, 1, 1, 1, 1),
            new HistoryItemDTO('symbol', '2025-02-01', 1, 1, 1, 1, 1),
            new HistoryItemDTO('symbol', '2025-02-15', 1, 1, 1, 1, 1),
            new HistoryItemDTO('symbol', '2025-02-16', 1, 1, 1, 1, 1),
            new HistoryItemDTO('symbol', '2025-02-28', 1, 1, 1, 1, 1),
        ];

        $expectedResponse = [
            new HistoryItemDTO('symbol', '2025-01-04', 1, 1, 1, 1, 1),
            new HistoryItemDTO('symbol', '2025-01-16', 1, 1, 1, 1, 1),
            new HistoryItemDTO('symbol', '2025-01-31', 1, 1, 1, 1, 1),
            new HistoryItemDTO('symbol', '2025-02-01', 1, 1, 1, 1, 1),
            new HistoryItemDTO('symbol', '2025-02-15', 1, 1, 1, 1, 1),
        ];

        $stockRequestDTO = new StockRequestDTO('symbol', '2025-01-04', '2025-02-15', 'test@test.test');
        $transformer = $this->createMock(HistoryItemTransformer::class);

        $apiClient = $this->createMock(HistoricalDataApiClientInterface::class);
        $apiClient->expects($this->once())->method('fetchHistoricalData')->with(
            'symbol',
            $testFirstMonthStart,
            DateTimeImmutable::createFromFormat(DateFormat::ISO_DATE, '2025-02-28')->setTime(0, 0)
        )->willReturn($apiResponse);

        $historicalDataCache = $this->createMock(HistoricalDataCache::class);
        $historicalDataCache->expects($this->once())->method('getFromRequestCache')->with($stockRequestDTO)->willReturn(
            null
        );
        $historicalDataCache->expects($this->once())->method('findMissingMonths')->with(
            'symbol', [$testFirstMonthStart, $testSecondMonthStart]
        )->willReturn([$testFirstMonthStart, $testSecondMonthStart]);


        $storeMonthlySegmentCalls = [];
        $historicalDataCache->expects($this->exactly(2))->method('storeMonthlySegment')->with(
            'symbol'
        )->willReturnCallback(
            function (string $symbol, DateTimeImmutable $month, array $segmentData) use (&$storeMonthlySegmentCalls) {
                $storeMonthlySegmentCalls[] = [
                    $month,
                    $segmentData,
                ];
            }
        );

        $getMonthlySegmentCalls = [];
        $historicalDataCache->expects($this->exactly(2))->method('getMonthlySegment')->with(
            'symbol'
        )->willReturnCallback(
            function (string $symbol, DateTimeImmutable $month) use (&$getMonthlySegmentCalls, $apiResponse) {
                $getMonthlySegmentCalls[] = $month;

                $results = function () use ($apiResponse) {
                    yield array_splice($apiResponse, 0, 4);
                    yield array_splice($apiResponse, 0, 4);
                };

                static $generator;
                if (null === $generator) {
                    $generator = $results();
                }
                $result = $generator->current();
                $generator->next();

                return $result;
            }
        );

        $historicalDataCache->expects($this->once())->method('saveToRequestCache')->with(
            $stockRequestDTO,
            $expectedResponse
        );

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('error');

        $service = new HistoricalDataService(
            $apiClient, $transformer, $historicalDataCache, $logger
        );
        $result = $service->getHistoricalData($stockRequestDTO);

        assertEquals(
            [
                [$testFirstMonthStart, array_splice($apiResponse, 0, 4)],
                [$testSecondMonthStart, array_splice($apiResponse, 0, 4)],
            ],
            $storeMonthlySegmentCalls,
            'Error in StoreMonthlySegment'
        );

        assertEquals(
            [
                $testFirstMonthStart,
                $testSecondMonthStart,
            ],
            $getMonthlySegmentCalls,
            'Error in GetMonthlySegment'
        );
        assertEquals($expectedResponse, $result);
    }

    public function testGetHistoricalData_notInRequestCache_partlyInLongTermCache()
    {
        $testFirstMonthStart = DateTimeImmutable::createFromFormat(DateFormat::ISO_DATE, '2025-01-01')->setTime(0, 0);
        $testSecondMonthStart = DateTimeImmutable::createFromFormat(DateFormat::ISO_DATE, '2025-02-01')->setTime(0, 0);

        $apiResponse = [
            new HistoryItemDTO('symbol', '2025-02-01', 1, 1, 1, 1, 1),
            new HistoryItemDTO('symbol', '2025-02-15', 1, 1, 1, 1, 1),
            new HistoryItemDTO('symbol', '2025-02-16', 1, 1, 1, 1, 1),
            new HistoryItemDTO('symbol', '2025-02-28', 1, 1, 1, 1, 1),
        ];

        $expectedResponse = [
            new HistoryItemDTO('symbol', '2025-01-04', 1, 1, 1, 1, 1),
            new HistoryItemDTO('symbol', '2025-01-16', 1, 1, 1, 1, 1),
            new HistoryItemDTO('symbol', '2025-01-31', 1, 1, 1, 1, 1),
            new HistoryItemDTO('symbol', '2025-02-01', 1, 1, 1, 1, 1),
            new HistoryItemDTO('symbol', '2025-02-15', 1, 1, 1, 1, 1),
        ];

        $stockRequestDTO = new StockRequestDTO('symbol', '2025-01-04', '2025-02-15', 'test@test.test');
        $transformer = $this->createMock(HistoryItemTransformer::class);

        $apiClient = $this->createMock(HistoricalDataApiClientInterface::class);
        $apiClient->expects($this->once())->method('fetchHistoricalData')->with(
            'symbol',
            $testSecondMonthStart,
            DateTimeImmutable::createFromFormat(DateFormat::ISO_DATE, '2025-02-28')->setTime(0, 0)
        )->willReturn($apiResponse);

        $historicalDataCache = $this->createMock(HistoricalDataCache::class);
        $historicalDataCache->expects($this->once())->method('getFromRequestCache')->with($stockRequestDTO)->willReturn(
            null
        );
        $historicalDataCache->expects($this->once())->method('findMissingMonths')->with(
            'symbol', [$testFirstMonthStart, $testSecondMonthStart]
        )->willReturn([$testSecondMonthStart]);


        $historicalDataCache->expects($this->once())->method('storeMonthlySegment')->with(
            'symbol',
            $testSecondMonthStart,
            $apiResponse
        );

        $getMonthlySegmentCalls = [];
        $historicalDataCache->expects($this->exactly(2))->method('getMonthlySegment')->with(
            'symbol'
        )->willReturnCallback(function (string $symbol, DateTimeImmutable $month) use (
            &$getMonthlySegmentCalls,
            $apiResponse
        ) {
            $getMonthlySegmentCalls[] = $month;

            $results = function () use ($apiResponse) {
                yield [
                    new HistoryItemDTO('symbol', '2025-01-01', 1, 1, 1, 1, 1),
                    new HistoryItemDTO('symbol', '2025-01-04', 1, 1, 1, 1, 1),
                    new HistoryItemDTO('symbol', '2025-01-16', 1, 1, 1, 1, 1),
                    new HistoryItemDTO('symbol', '2025-01-31', 1, 1, 1, 1, 1),
                ];
                yield $apiResponse;
            };

            static $generator;
            if (null === $generator) {
                $generator = $results();
            }
            $result = $generator->current();
            $generator->next();

            return $result;
        });

        $historicalDataCache->expects($this->once())->method('saveToRequestCache')->with(
            $stockRequestDTO,
            $expectedResponse
        );

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('error');

        $service = new HistoricalDataService(
            $apiClient, $transformer, $historicalDataCache, $logger
        );
        $result = $service->getHistoricalData($stockRequestDTO);

        assertEquals(
            [
                $testFirstMonthStart,
                $testSecondMonthStart,
            ],
            $getMonthlySegmentCalls,
            'Error in GetMonthlySegment'
        );
        assertEquals($expectedResponse, $result);
    }

    public function testGetHistoricalData_notInRequestCache_completelyInLongTermCache()
    {
        $testFirstMonthStart = DateTimeImmutable::createFromFormat(DateFormat::ISO_DATE, '2025-01-01')->setTime(0, 0);
        $testSecondMonthStart = DateTimeImmutable::createFromFormat(DateFormat::ISO_DATE, '2025-02-01')->setTime(0, 0);

        $expectedResponse = [
            new HistoryItemDTO('symbol', '2025-01-04', 1, 1, 1, 1, 1),
            new HistoryItemDTO('symbol', '2025-01-16', 1, 1, 1, 1, 1),
            new HistoryItemDTO('symbol', '2025-01-31', 1, 1, 1, 1, 1),
            new HistoryItemDTO('symbol', '2025-02-01', 1, 1, 1, 1, 1),
            new HistoryItemDTO('symbol', '2025-02-15', 1, 1, 1, 1, 1),
        ];

        $stockRequestDTO = new StockRequestDTO('symbol', '2025-01-04', '2025-02-15', 'test@test.test');
        $transformer = $this->createMock(HistoryItemTransformer::class);

        $apiClient = $this->createMock(HistoricalDataApiClientInterface::class);
        $apiClient->expects($this->never())->method('fetchHistoricalData');

        $historicalDataCache = $this->createMock(HistoricalDataCache::class);
        $historicalDataCache->expects($this->once())->method('getFromRequestCache')->with($stockRequestDTO)->willReturn(
            null
        );
        $historicalDataCache->expects($this->once())->method('findMissingMonths')->with(
            'symbol', [$testFirstMonthStart, $testSecondMonthStart]
        )->willReturn([]);


        $historicalDataCache->expects($this->never())->method('storeMonthlySegment');

        $getMonthlySegmentCalls = [];
        $historicalDataCache->expects($this->exactly(2))->method('getMonthlySegment')->with(
            'symbol'
        )->willReturnCallback(function (string $symbol, DateTimeImmutable $month) use (&$getMonthlySegmentCalls) {
            $getMonthlySegmentCalls[] = $month;

            $results = function () {
                yield [
                    new HistoryItemDTO('symbol', '2025-01-01', 1, 1, 1, 1, 1),
                    new HistoryItemDTO('symbol', '2025-01-04', 1, 1, 1, 1, 1),
                    new HistoryItemDTO('symbol', '2025-01-16', 1, 1, 1, 1, 1),
                    new HistoryItemDTO('symbol', '2025-01-31', 1, 1, 1, 1, 1),
                ];
                yield [
                    new HistoryItemDTO('symbol', '2025-02-01', 1, 1, 1, 1, 1),
                    new HistoryItemDTO('symbol', '2025-02-15', 1, 1, 1, 1, 1),
                    new HistoryItemDTO('symbol', '2025-02-16', 1, 1, 1, 1, 1),
                    new HistoryItemDTO('symbol', '2025-02-28', 1, 1, 1, 1, 1),
                ];
            };

            static $generator;
            if (null === $generator) {
                $generator = $results();
            }
            $result = $generator->current();
            $generator->next();

            return $result;
        });

        $historicalDataCache->expects($this->once())->method('saveToRequestCache')->with(
            $stockRequestDTO,
            $expectedResponse
        );

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('error');

        $service = new HistoricalDataService(
            $apiClient, $transformer, $historicalDataCache, $logger
        );
        $result = $service->getHistoricalData($stockRequestDTO);

        assertEquals(
            [
                $testFirstMonthStart,
                $testSecondMonthStart,
            ],
            $getMonthlySegmentCalls,
            'Error in GetMonthlySegment'
        );
        assertEquals($expectedResponse, $result);
    }

    public function testGetHistoricalData_notInRequestCache_notInLongTermCache_apiError()
    {
        $testMonthStart = DateTimeImmutable::createFromFormat(DateFormat::ISO_DATE, '2025-01-01')->setTime(0, 0);

        $expectedResponse = [];

        $stockRequestDTO = new StockRequestDTO('symbol', '2025-01-04', '2025-01-15', 'test@test.test');
        $transformer = $this->createMock(HistoryItemTransformer::class);

        $apiClient = $this->createMock(HistoricalDataApiClientInterface::class);
        $apiClient->method('fetchHistoricalData')->willThrowException(new Exception('Test api error'));

        $historicalDataCache = $this->createMock(HistoricalDataCache::class);
        $historicalDataCache->expects($this->once())->method('getFromRequestCache')->with($stockRequestDTO)->willReturn(
            null
        );
        $historicalDataCache->expects($this->once())->method('findMissingMonths')->willReturn([$testMonthStart]);
        $historicalDataCache->expects($this->never())->method('storeMonthlySegment');
        $historicalDataCache->expects($this->once())->method('getMonthlySegment')->willReturn([]);
        $historicalDataCache->expects($this->never())->method('saveToRequestCache');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error')->with(
            'Failed to fetch historical data', [
                'symbol' => 'symbol',
                'from' => '2025-01-01',
                'to' => '2025-01-31',
                'error' => 'Test api error',
            ]
        );

        $service = new HistoricalDataService(
            $apiClient, $transformer, $historicalDataCache, $logger
        );
        $result = $service->getHistoricalData($stockRequestDTO);
        assertEquals($expectedResponse, $result);
    }

}
