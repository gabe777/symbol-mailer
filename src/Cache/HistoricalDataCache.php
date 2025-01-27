<?php

declare(strict_types=1);

namespace App\Cache;

use App\DTO\HistoryItemDTO;
use App\DTO\StockRequestDTO;
use App\Enum\CacheKeys;
use DateTimeImmutable;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;

class HistoricalDataCache
{
    private const string CACHE_KEY_MONTH = 'historical_data_{symbol}_{year}_{month}';
    private const int CACHE_TTL_SHORT = 86400;

    public function __construct(
        private readonly CacheInterface $cache,
    ) {
    }


    public function getFromRequestCache(StockRequestDTO $stockRequestDTO): ?array
    {
        return $this->cache->get(
            CacheKeys::CACHE_KEY_REQUEST->generateKey([
                'symbol' => $stockRequestDTO->companySymbol,
                'from' => $stockRequestDTO->startDate,
                'to' => $stockRequestDTO->endDate,
            ]), fn() => null
        );
    }

    public function saveToRequestCache(StockRequestDTO $stockRequestDTO, array $data): void
    {
        $this->set(
            CacheKeys::CACHE_KEY_REQUEST->generateKey([
                'symbol' => $stockRequestDTO->companySymbol,
                'from' => $stockRequestDTO->startDate,
                'to' => $stockRequestDTO->endDate,
            ]),
            $data,
            self::CACHE_TTL_SHORT
        );
    }

    /**
     * @param string $symbol
     * @param DateTimeImmutable $month
     * @param HistoryItemDTO[] $data
     *
     * @return void
     *
     */
    public function storeMonthlySegment(string $symbol, DateTimeImmutable $month, array $data): void
    {
        $this->set(
            CacheKeys::CACHE_KEY_MONTH->generateKey([
                'symbol' => $symbol,
                'year' => $month->format('Y'),
                'month' => $month->format('m'),
            ]),
            $data,
            $month->format('Ym') == (new DateTimeImmutable())->format('Ym') ? self::CACHE_TTL_SHORT : null
        );
    }

    /**
     * @param string $symbol
     * @param DateTimeImmutable $month
     *
     * @return HistoryItemDTO[]|null
     *
     * @throws InvalidArgumentException
     */
    public function getMonthlySegment(
        string $symbol,
        DateTimeImmutable $month
    ): ?array {
        $item = $this->cache->getItem(CacheKeys::CACHE_KEY_MONTH->generateKey([
            'symbol' => $symbol,
            'year' => $month->format('Y'),
            'month' => $month->format('m'),
        ]));

        return $item->isHit() ? $item->get() : null;
    }

    /**
     * @param string $symbol
     * @param DateTimeImmutable[] $monthRange
     *
     * @return DateTimeImmutable[]
     *
     * @throws InvalidArgumentException
     */
    public function findMissingMonths(
        string $symbol,
        array $monthRange
    ): array {
        $missingMonths = [];

        foreach ($monthRange as $month) {
            if (!$this->getMonthlySegment($symbol, $month)) {
                $missingMonths[] = $month;
            }
        }

        return $missingMonths;
    }


    private function set(string $cacheKey, mixed $data, ?int $expiresAfter): void
    {
        $cacheItem = $this->cache->getItem($cacheKey);
        $cacheItem->expiresAfter($expiresAfter)->set($data);
        $this->cache->save($cacheItem);
    }
}