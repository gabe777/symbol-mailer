<?php

declare(strict_types=1);

namespace App\Service;

class RateLimiterService
{
    private string $rateLimiterKey;

    public function setRateLimiterKey(string $key): RateLimiterService
    {
        $this->rateLimiterKey = $key;

        return $this;
    }

    public function registerAccess(): RateLimiterService
    {
        return $this;
    }

    public function isLimitExceeded(): bool
    {
        return false;
    }
}