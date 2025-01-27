<?php

namespace App\MessageHandler;

use App\Cache\HistoricalDataCache;
use App\Message\SendHistoryEmailMessage;
use App\Service\HistoricalDataCsvService;
use App\Service\HistoricalDataMailService;
use App\Service\SymbolService;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Exception\CacheException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class SendHistoryEmailHandler
{
    public function __construct(
        private readonly SymbolService $symbolService,
        private readonly HistoricalDataCache $historicalDataCache,
        private readonly HistoricalDataMailService $mailService,
        private readonly HistoricalDataCsvService $historicalDataCsvService,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(SendHistoryEmailMessage $message): void
    {
        try {
            $stockRequestDTO = $message->stockRequestDTO;
            $historicalData = $this->historicalDataCache->getFromRequestCache($stockRequestDTO);
            $companyName = $this->symbolService->getSymbolInfo($stockRequestDTO->companySymbol)->companyName;

            if (empty($historicalData)) {
                throw new CacheException('Historical data not found in cache for ' . $companyName);
            }

            $csv = $this->historicalDataCsvService->createFromString();
            $this->historicalDataCsvService->addHeaders($csv);
            $this->historicalDataCsvService->addHistoricalData(
                $csv,
                $historicalData
            );

            $this->mailService->send(
                $stockRequestDTO->email,
                $companyName,
                "From $stockRequestDTO->startDate to $stockRequestDTO->endDate",
                $csv->toString()
            );
            $this->logger->info('Historical Data email sent to '.$stockRequestDTO->email.' about '.$companyName);
        } catch (Exception $e) {
            $this->logger->error('Historical Data email sending failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
