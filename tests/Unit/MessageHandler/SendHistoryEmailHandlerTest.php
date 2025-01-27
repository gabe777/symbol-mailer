<?php

namespace App\Tests\Unit\MessageHandler;

use App\Cache\HistoricalDataCache;
use App\DTO\HistoryItemDTO;
use App\DTO\StockRequestDTO;
use App\DTO\SymbolInfoDTO;
use App\Message\SendHistoryEmailMessage;
use App\MessageHandler\SendHistoryEmailHandler;
use App\Service\HistoricalDataCsvService;
use App\Service\HistoricalDataMailService;
use App\Service\SymbolService;
use Exception;
use League\Csv\Writer;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SendHistoryEmailHandlerTest extends TestCase
{
    public function testInvoke_success()
    {
        $stockRequestDTO = new StockRequestDTO('symbol', '2025-01-01', '2025-01-02', 'test@test.test');
        $message = new SendHistoryEmailMessage(
            $stockRequestDTO
        );

        $abstractCsv = $this->createMock(Writer::class);
        $abstractCsv->expects($this->once())->method('toString')->willReturn('testCsvString');

        $csvService = $this->createMock(HistoricalDataCsvService::class);
        $csvService->expects($this->once())->method('createFromString')->willReturn($abstractCsv);

        $mailService = $this->createMock(HistoricalDataMailService::class);
        $mailService->expects($this->once())->method('send')->with(
            'test@test.test',
            'company',
            'From 2025-01-01 to 2025-01-02',
            'testCsvString'
        );

        $symbolService = $this->createMock(SymbolService::class);
        $symbolService->expects($this->once())->method('getSymbolInfo')->with('symbol')->willReturn(
            new SymbolInfoDTO('symbol', 'company')
        );

        $historicalDataCache = $this->createMock(HistoricalDataCache::class);
        $historicalDataCache->expects($this->once())->method('getFromRequestCache')->with($stockRequestDTO)->willReturn(
            [new HistoryItemDTO('symbol', '2012-01-01',1,1,1,1,1,)]
        );

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('info');
        $logger->expects($this->never())->method('error');

        (new SendHistoryEmailHandler($symbolService, $historicalDataCache, $mailService, $csvService, $logger))(
            $message
        );
    }

    public function testInvoke_failure()
    {
        $stockRequestDTO = new StockRequestDTO('symbol', '2025-01-01', '2025-01-02', 'test@test.test');
        $message = new SendHistoryEmailMessage(
            $stockRequestDTO
        );

        $abstractCsv = $this->createMock(Writer::class);
        $abstractCsv->expects($this->once())->method('toString')->willThrowException(new Exception('testException'));

        $csvService = $this->createMock(HistoricalDataCsvService::class);
        $csvService->expects($this->once())->method('createFromString')->willReturn($abstractCsv);

        $mailService = $this->createMock(HistoricalDataMailService::class);
        $mailService->expects($this->never())->method('send');

        $symbolService = $this->createMock(SymbolService::class);
        $symbolService->expects($this->once())->method('getSymbolInfo')->with('symbol')->willReturn(
            new SymbolInfoDTO('symbol', 'company')
        );

        $historicalDataCache = $this->createMock(HistoricalDataCache::class);
        $historicalDataCache->expects($this->once())->method('getFromRequestCache')->with($stockRequestDTO)->willReturn(
            [new HistoryItemDTO('symbol', '2012-01-01',1,1,1,1,1,)]
        );

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error')->with('Historical Data email sending failed', [
            'error' => 'testException',
        ]);
        (new SendHistoryEmailHandler($symbolService, $historicalDataCache, $mailService, $csvService, $logger))(
            $message
        );
    }

    public function testInvoke_cacheFailure()
    {
        $stockRequestDTO = new StockRequestDTO('symbol', '2025-01-01', '2025-01-02', 'test@test.test');
        $message = new SendHistoryEmailMessage(
            $stockRequestDTO
        );

        $abstractCsv = $this->createMock(Writer::class);
        $abstractCsv->expects($this->never())->method('toString');

        $csvService = $this->createMock(HistoricalDataCsvService::class);
        $csvService->expects($this->never())->method('createFromString');

        $mailService = $this->createMock(HistoricalDataMailService::class);
        $mailService->expects($this->never())->method('send');

        $symbolService = $this->createMock(SymbolService::class);
        $symbolService->expects($this->once())->method('getSymbolInfo')->with('symbol')->willReturn(
            new SymbolInfoDTO('symbol', 'company')
        );

        $historicalDataCache = $this->createMock(HistoricalDataCache::class);
        $historicalDataCache->expects($this->once())->method('getFromRequestCache')->with($stockRequestDTO)->willReturn(
            null
        );

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error')->with('Historical Data email sending failed', [
            'error' => 'Historical data not found in cache for company',
        ]);
        (new SendHistoryEmailHandler($symbolService, $historicalDataCache, $mailService, $csvService, $logger))(
            $message
        );
    }
}
