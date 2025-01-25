<?php

declare(strict_types=1);

namespace App\Cache;

use App\DTO\StockRequestDTO;
use Symfony\Contracts\Cache\CacheInterface;

class HistoricalDataCache
{
    private const string CACHE_KEY_REQUEST = 'request_{symbol}_{from}-{to}';
    private const int CACHE_TTL_REQUEST = 86400;

    public function __construct(private readonly CacheInterface $cache)
    {
    }

    public function getFromRequestCache(StockRequestDTO $stockRequestDTO): ?array
    {
        $cacheKey = strtr(self::CACHE_KEY_REQUEST, [
            '{symbol}' => $stockRequestDTO->companySymbol,
            '{from}' => $stockRequestDTO->startDate,
            '{to}' => $stockRequestDTO->endDate,
        ]);

        return $this->cache->get($cacheKey, fn() => null);
    }

    public function saveToRequestCache(StockRequestDTO $stockRequestDTO, array $data): void
    {
        $cacheKey = strtr(self::CACHE_KEY_REQUEST, [
            '{symbol}' => $stockRequestDTO->companySymbol,
            '{from}' => $stockRequestDTO->startDate,
            '{to}' => $stockRequestDTO->endDate,
        ]);
        $this->set($cacheKey, self::CACHE_TTL_REQUEST, $data);
    }

    protected function set(string $cacheKey, int $expiresAfter, mixed $data): void
    {
        $cacheItem = $this->cache->getItem($cacheKey);
        $cacheItem->set($data)->expiresAfter($expiresAfter);
        $this->cache->save($cacheItem);
    }
}