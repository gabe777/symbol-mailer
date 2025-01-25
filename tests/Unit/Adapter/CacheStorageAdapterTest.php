<?php

namespace App\Tests\Unit\Adapter;

use App\Adapter\CacheStorageAdapter;
use App\DTO\SymbolInfoDTO;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertTrue;

class CacheStorageAdapterTest extends TestCase
{

    private CacheInterface|MockObject $cache;

    protected function setUp(): void
    {
        $this->cache = new ArrayAdapter();
    }

    public function testGetSymbols_found()
    {
        $symbolInfoDtoArray = [
            new SymbolInfoDTO('TST', 'Test Ltd.'),
            new SymbolInfoDTO('MCK', 'Mock Inc.'),
        ];

        $this->cache->save($this->cache->getItem('company_symbols')->set($symbolInfoDtoArray));

        $adapter = new CacheStorageAdapter($this->cache);
        assertEquals($symbolInfoDtoArray, $adapter->getSymbols());
    }

    public function testGetSymbols_notFound()
    {
        $adapter = new CacheStorageAdapter($this->cache);
        assertNull($adapter->getSymbols());
    }

    public function testSaveSymbols()
    {
        $symbolInfoDtoArray = [
            new SymbolInfoDTO('TST', 'Test Ltd.'),
            new SymbolInfoDTO('MCK', 'Mock Inc.'),
        ];


        assertFalse($this->cache->getItem('company_symbols')->isHit());

        $adapter = new CacheStorageAdapter($this->cache);
        $adapter->saveSymbols($symbolInfoDtoArray);

        assertTrue($this->cache->getItem('company_symbols')->isHit());
        assertEquals($symbolInfoDtoArray, $adapter->getSymbols());
    }

    public function testSaveSymbols_ttl()
    {
        $symbolInfoDtoArray = [
            new SymbolInfoDTO('TST', 'Test Ltd.'),
            new SymbolInfoDTO('MCK', 'Mock Inc.'),
        ];

        $cache = $this->createMock(CacheInterface::class);
        $cacheItem = $this->createMock(ItemInterface::class);

        $cacheItem->expects($this->once())->method('expiresAfter')->with(86400);

        $cache->expects($this->once())->method('get')->with(
            'company_symbols',
            $this->isType('callable')
        )->willReturnCallback(function ($key, $callback) use ($cacheItem) {
            $callback($cacheItem);
        });

        $adapter = new CacheStorageAdapter($cache);
        $adapter->saveSymbols($symbolInfoDtoArray);
    }
}
