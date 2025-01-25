<?php

namespace App\MessageHandler;

use App\Message\SendHistoryEmailMessage;
use App\Service\HistoricalDataCsvService;
use App\Service\HistoricalDataMailService;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class SendHistoryEmailHandler
{
    public function __construct(
        private readonly HistoricalDataMailService $mailService,
        private readonly HistoricalDataCsvService $historicalDataCsvService,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(SendHistoryEmailMessage $message): void
    {
        try {
            $csv = $this->historicalDataCsvService->createFromString();
            $this->historicalDataCsvService->addHeaders($csv);
            $this->historicalDataCsvService->addHistoricalData($csv, $message->historicalData);

            $this->mailService->send(
                $message->email,
                $message->companyName,
                "From $message->startDate to $message->endDate",
                $csv->toString()
            );
            $this->logger->info('Historical Data email sent to '.$message->email.' about '.$message->companyName);
        } catch (Exception $e) {
            $this->logger->error('Historical Data email sending failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
