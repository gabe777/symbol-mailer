<?php

declare(strict_types=1);

namespace App\Adapter;

use App\DTO\SymbolInfoDTO;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CacheStorageAdapter implements StorageAdapterInterface
{
    private const string CACHE_KEY_COMPANY_SYMBOLS = 'company_symbols';
    private const int CACHE_TTL_COMPANY_SYMBOLS = 86400;

    public function __construct(private readonly CacheInterface $cache)
    {
    }

    public function saveSymbols(array $symbols): void
    {
        $this->cache->get(self::CACHE_KEY_COMPANY_SYMBOLS, function (ItemInterface $item) use ($symbols) {
            $item->expiresAfter(self::CACHE_TTL_COMPANY_SYMBOLS);

            return $symbols;
        });
    }

    /**
     * @return SymbolInfoDTO[]|null
     * @throws InvalidArgumentException
     */
    public function getSymbols(): ?array
    {
        return $this->cache->getItem(self::CACHE_KEY_COMPANY_SYMBOLS)->get();
    }
}