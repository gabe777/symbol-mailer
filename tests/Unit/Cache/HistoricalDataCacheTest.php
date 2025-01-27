<?php

namespace App\Tests\Unit\Cache;

use App\Cache\HistoricalDataCache;
use App\Constant\DateFormat;
use App\DTO\StockRequestDTO;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNull;

class HistoricalDataCacheTest extends TestCase
{

    private CacheInterface|MockObject $cache;
    private StockRequestDTO $stockRequestDTO;

    protected function setUp(): void
    {
        $this->cache = new ArrayAdapter();
        $this->stockRequestDTO = new StockRequestDTO(
            'symbol', '2025-01-04', '2025-02-16', 'test@test.test'
        );
    }

    public function testRequestCache()
    {
        $testData = ['testData'];
        $dataCache = new HistoricalDataCache($this->cache);

        assertNull($dataCache->getFromRequestCache($this->stockRequestDTO));
        $dataCache->saveToRequestCache($this->stockRequestDTO, $testData);
        $this->cache->hasItem('request_symbol_2025-01-14_2025_02_16');
        assertEquals($testData, $dataCache->getFromRequestCache($this->stockRequestDTO));
    }

    public function testMonthlySegment()
    {
        $testData = ['testData'];
        $month = DateTimeImmutable::createFromFormat(DateFormat::ISO_DATE, '2025-01-01');
        $dataCache = new HistoricalDataCache($this->cache);

        assertNull($dataCache->getMonthlySegment($this->stockRequestDTO->companySymbol, $month));
        $dataCache->storeMonthlySegment($this->stockRequestDTO->companySymbol, $month, $testData);
        $this->cache->hasItem('historical_data_symbol_2025-01');
        assertEquals($testData, $dataCache->getMonthlySegment($this->stockRequestDTO->companySymbol, $month));
    }

    public function testStoreMonthlySegmentTtl_currentMonth()
    {
        $currentMonth = DateTimeImmutable::createFromFormat('', '')->modify('first day of this month');

        $cacheItem = $this->createMock(ItemInterface::class);
        $cacheItem->expects($this->once())->method('expiresAfter')->with(
            $this->logicalAnd($this->isType('int'), $this->greaterThan(0))
        )->willReturn($cacheItem);

        $cache = $this->getCacheImplementation($cacheItem);

        $historicalDataCache = new HistoricalDataCache($cache);
        $historicalDataCache->storeMonthlySegment('symbol', $currentMonth, []);
    }

    public function testStoreMonthlySegmentTtl_olderMonth()
    {
        $currentMonth = DateTimeImmutable::createFromFormat('', '')->modify('first day of next month');

        $cacheItem = $this->createMock(ItemInterface::class);
        $cacheItem->expects($this->once())->method('expiresAfter')->with(
            $this->isNull()
        )->willReturn($cacheItem);

        $cache = $this->getCacheImplementation($cacheItem);

        $historicalDataCache = new HistoricalDataCache($cache);
        $historicalDataCache->storeMonthlySegment('symbol', $currentMonth, []);
    }

    public function testFindMissingMonths()
    {
        $cachedMonth = DateTimeImmutable::createFromFormat(DateFormat::ISO_DATE, '2025-01-01');
        $nonCachedPrevious = $cachedMonth->modify('first day of previous month');
        $nonCachedNext = $cachedMonth->modify('first day of next month');
        $dataCache = new HistoricalDataCache($this->cache);
        $dataCache->storeMonthlySegment($this->stockRequestDTO->companySymbol, $cachedMonth, ['cached']);

        $missingMonths = $dataCache->findMissingMonths(
            $this->stockRequestDTO->companySymbol, [
                $nonCachedPrevious,
                $cachedMonth,
                $nonCachedNext,
            ]
        );
        assertEquals([$nonCachedPrevious, $nonCachedNext], $missingMonths);
    }

    private function getCacheImplementation(ItemInterface $cacheItem): CacheInterface
    {
        return new readonly class($cacheItem) implements CacheInterface {
            public function __construct(private ItemInterface $item)
            {
            }

            public function save(ItemInterface $item): void
            {
            }

            public function get(string $key, callable $callback, ?float $beta = null, ?array &$metadata = null): mixed
            {
                return null;
            }

            public function delete(string $key): bool
            {
                return true;
            }

            public function getItem(string $key): ItemInterface
            {
                return $this->item;
            }
        };
    }
}
