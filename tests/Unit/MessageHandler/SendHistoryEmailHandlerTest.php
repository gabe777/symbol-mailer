<?php

namespace App\Tests\Unit\MessageHandler;

use App\Message\SendHistoryEmailMessage;
use App\MessageHandler\SendHistoryEmailHandler;
use App\Service\HistoricalDataCsvService;
use App\Service\HistoricalDataMailService;
use Exception;
use League\Csv\AbstractCsv;
use League\Csv\Writer;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SendHistoryEmailHandlerTest extends TestCase
{
    public function testInvoke_success()
    {
        $message = new SendHistoryEmailMessage(
            'company', '2025-01-01', '2025-01-02', 'test@test.test', []
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

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('info');
        $logger->expects($this->never())->method('error');

        (new SendHistoryEmailHandler($mailService, $csvService, $logger))($message);
    }

    public function testInvoke_failure()
    {
        $message = new SendHistoryEmailMessage(
            'company', '2025-01-01', '2025-01-02', 'test@test.test', []
        );

        $abstractCsv = $this->createMock(Writer::class);
        $abstractCsv->expects($this->once())->method('toString')->willThrowException(new Exception('testException'));

        $csvService = $this->createMock(HistoricalDataCsvService::class);
        $csvService->expects($this->once())->method('createFromString')->willReturn($abstractCsv);

        $mailService = $this->createMock(HistoricalDataMailService::class);
        $mailService->expects($this->never())->method('send');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error')->with('Historical Data email sending failed', [
            'error' => 'testException',
        ]);
        (new SendHistoryEmailHandler($mailService, $csvService, $logger))($message);
    }
}
