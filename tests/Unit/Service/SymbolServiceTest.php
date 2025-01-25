<?php

namespace App\Tests\Unit\Service;

use App\Adapter\StorageAdapterInterface;
use App\ApiClient\SymbolInfo\DataHubApiClient;
use App\DTO\SymbolInfoDTO;
use App\Service\SymbolService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertTrue;

class SymbolServiceTest extends TestCase
{

    private StorageAdapterInterface|MockObject $storageAdapter;
    private DataHubApiClient|MockObject $apiClient;

    protected function setUp(): void
    {
        $this->storageAdapter = $this->createMock(StorageAdapterInterface::class);
        $this->apiClient = $this->createMock(DataHubApiClient::class);
    }

    public function testGetSymbolInfo_found_cache()
    {
        $symbolInfoDtoArray = [
            new SymbolInfoDTO('TST', 'Test Ltd.'),
            new SymbolInfoDTO('MCK', 'Mock Inc.'),
        ];
        $this->storageAdapter->expects($this->once())->method('getSymbols')->willReturn($symbolInfoDtoArray);
        $this->storageAdapter->expects($this->never())->method('saveSymbols');

        $this->apiClient->expects($this->never())->method('fetchSymbolInfo');

        $symbolService = new SymbolService($this->storageAdapter, $this->apiClient);
        $result = $symbolService->getSymbolInfo('mck');
        assertSame($symbolInfoDtoArray[1], $result);
    }

    public function testGetSymbolInfo_found_notInCache()
    {
        $symbolInfoDtoArray = [
            new SymbolInfoDTO('TST', 'Test Ltd.'),
            new SymbolInfoDTO('MCK', 'Mock Inc.'),
        ];
        $this->storageAdapter->method('getSymbols')->willReturn(null);
        $this->storageAdapter->expects($this->once())->method('saveSymbols')->with($symbolInfoDtoArray);

        $this->apiClient->expects($this->once())->method('fetchSymbolInfo')->willReturn($symbolInfoDtoArray);

        $symbolService = new SymbolService($this->storageAdapter, $this->apiClient);
        $result = $symbolService->getSymbolInfo('TST');
        assertSame($symbolInfoDtoArray[0], $result);
    }

    public function testGetSymbolInfo_notFound()
    {
        $symbolInfoDtoArray = [
            new SymbolInfoDTO('TST', 'Test Ltd.'),
            new SymbolInfoDTO('MCK', 'Mock Inc.'),
        ];

        $this->storageAdapter->method('getSymbols')->willReturn($symbolInfoDtoArray);

        $symbolService = new SymbolService($this->storageAdapter, $this->apiClient);
        $result = $symbolService->getSymbolInfo('IXNT');
        assertNull($result);
    }

    public function testIsValidSymbol_valid()
    {
        $symbolInfoDtoArray = [
            new SymbolInfoDTO('TST', 'Test Ltd.'),
            new SymbolInfoDTO('MCK', 'Mock Inc.'),
        ];
        $this->storageAdapter->method('getSymbols')->willReturn($symbolInfoDtoArray);
        $symbolService = new SymbolService($this->storageAdapter, $this->apiClient);
        assertTrue($symbolService->isValidSymbol('mck'));
    }

    public function testIsValidSymbol_invalid()
    {
        $symbolInfoDtoArray = [
            new SymbolInfoDTO('TST', 'Test Ltd.'),
            new SymbolInfoDTO('MCK', 'Mock Inc.'),
        ];
        $this->storageAdapter->method('getSymbols')->willReturn($symbolInfoDtoArray);
        $symbolService = new SymbolService($this->storageAdapter, $this->apiClient);
        assertFalse($symbolService->isValidSymbol('invalid'));
    }
}
