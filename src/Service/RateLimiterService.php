<?php

declare(strict_types=1);

namespace App\Service;

use App\Enum\CacheKeys;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class RateLimiterService
{
    private string $rateLimiterKey;

    public function __construct(
        private readonly CacheItemPoolInterface $cache,
        private readonly int $ttl,
        private readonly int $limit
    ) {
    }

    public function setRateLimiterKey(string $clientIp, string $apiKey): RateLimiterService
    {
        $this->rateLimiterKey = CacheKeys::CACHE_KEY_RATE_LIMITER->generateKey([
            'clientIp' => $clientIp,
            'apiKey' => $apiKey,
        ]);

        return $this;
    }

    public function registerAccess(): RateLimiterService
    {
        $counter = $this->getCounter();
        $hits = $counter->get();
        if ($hits <= $this->limit) {
            $counter->expiresAfter($this->ttl)->set(++$hits);
            $this->cache->save($counter);
        }

        return $this;
    }

    public function isLimitExceeded(): bool
    {
        return $this->getCounter()->get() > $this->limit;
    }

    private function getCounter(): CacheItemInterface
    {
        $item = $this->cache->getItem($this->rateLimiterKey);
        if (!$item->isHit()) {
            $item->expiresAfter($this->ttl)->set(0);
            $this->cache->save($item);
        }

        return $item;
    }
}

