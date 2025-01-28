<?php

namespace App\Tests\Unit\Service;

use App\Service\RateLimiterService;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertTrue;

class RateLimiterServiceTest extends TestCase
{
    public function testRegisterAccess_first()
    {
        $testTtl = 61;
        $testLimit = 11;

        $item = $this->createMock(CacheItemInterface::class);
        $item->expects($this->once())->method('isHit')->willReturn(false);
        $item->expects($this->exactly(2))->method('expiresAfter')->with($testTtl)->willReturnSelf();
        $item->expects($this->once())->method('get')->willReturn(0);
        $item->expects($this->exactly(2))->method('set')->with($this->callback(function ($param) {
            $expectedParams = function () {
                yield 0;
                yield 1;
            };

            static $generator;
            if (null === $generator) {
                $generator = $expectedParams();
            }

            $expectedParam = $generator->current();
            $generator->next();

            return $expectedParam === $param;
        }))->willReturnSelf();

        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cache->expects($this->once())->method('getItem')->with('rate_limiter_1_1')->willReturn($item);
        $cache->expects($this->exactly(2))->method('save')->with($item);

        $rateLimiter = new RateLimiterService($cache, $testTtl, $testLimit);
        $result = $rateLimiter->setRateLimiterKey('1', '1')->registerAccess();

        $this->assertEquals($rateLimiter, $result);
    }

    public function testRegisterAccess_notFirst()
    {
        $testTtl = 61;
        $testLimit = 11;

        $item = $this->createMock(CacheItemInterface::class);
        $item->expects($this->once())->method('isHit')->willReturn(true);
        $item->expects($this->once())->method('expiresAfter')->with($testTtl)->willReturnSelf();
        $item->expects($this->once())->method('get')->willReturn(1);
        $item->expects($this->once())->method('set')->with(2)->willReturnSelf();

        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cache->expects($this->once())->method('getItem')->with('rate_limiter_1_1')->willReturn($item);
        $cache->expects($this->once())->method('save')->with($item);

        $rateLimiter = new RateLimiterService($cache, $testTtl, $testLimit);
        $result = $rateLimiter->setRateLimiterKey('1', '1')->registerAccess();

        $this->assertEquals($rateLimiter, $result);
    }

    public function testRegisterAccess_noRefreshIfExceeded()
    {
        $testTtl = 10;
        $testLimit = 1;

        $item = $this->createMock(CacheItemInterface::class);
        $item->expects($this->once())->method('isHit')->willReturn(true);
        $item->expects($this->never())->method('expiresAfter');
        $item->expects($this->once())->method('get')->willReturn(2);
        $item->expects($this->never())->method('set');

        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cache->expects($this->once())->method('getItem')->with('rate_limiter_1_1')->willReturn($item);
        $cache->expects($this->never())->method('save');

        $rateLimiter = new RateLimiterService($cache, $testTtl, $testLimit);
        $result = $rateLimiter->setRateLimiterKey('1', '1')->registerAccess();

        $this->assertEquals($rateLimiter, $result);
    }

    public function testIsLimitExceeded()
    {
        $testTtl = 61;
        $testLimit = 11;
        $item = $this->createStub(CacheItemInterface::class);
        $item->method('isHit')->willReturn(true);
        $item->method('get')->willReturn(12);

        $cache = $this->createStub(CacheItemPoolInterface::class);
        $cache->method('getItem')->willReturn($item);

        $rateLimiter = new RateLimiterService($cache, $testTtl, $testLimit);
        $result = $rateLimiter->setRateLimiterKey('1', '1')->isLimitExceeded();
        assertTrue($result);

        $rateLimiter = new RateLimiterService($cache, $testTtl, 12);
        $result = $rateLimiter->setRateLimiterKey('1', '1')->isLimitExceeded();
        assertFalse($result);
    }

    public function testCycle()
    {
        $cache = new ArrayAdapter();

        $rateLimiter = new RateLimiterService($cache, 2, 1);
        assertNull($cache->get('rate_limiter_1_1', fn() => null));
        $rateLimiter->setRateLimiterKey('1', '1');

        $rateLimiter->registerAccess();
        assertEquals(1, $cache->get('rate_limiter_1_1', fn() => null));
        assertFalse($rateLimiter->isLimitExceeded());

        $rateLimiter->registerAccess();
        assertEquals(2, $cache->get('rate_limiter_1_1', fn() => null));
        assertTrue($rateLimiter->isLimitExceeded());
    }
}
