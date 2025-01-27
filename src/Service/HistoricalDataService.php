<?php

declare(strict_types=1);

namespace App\Service;

use App\ApiClient\HistoricalDataApiClientInterface;
use App\Cache\HistoricalDataCache;
use App\Constant\DateFormat;
use App\DTO\HistoryItemDTO;
use App\DTO\StockRequestDTO;
use App\Transformer\HistoryItemTransformer;
use DateTimeImmutable;
use Exception;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class HistoricalDataService
{
    public function __construct(
        private readonly HistoricalDataApiClientInterface $apiClient,
        private readonly HistoryItemTransformer $transformer,
        private readonly HistoricalDataCache $historicalDataCache,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param StockRequestDTO $stockRequestDTO
     *
     * @return HistoryItemDTO[]
     *
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws InvalidArgumentException
     */
    public function getHistoricalData(StockRequestDTO $stockRequestDTO): array
    {
        $cacheResult = $this->historicalDataCache->getFromRequestCache($stockRequestDTO);
        if (null !== $cacheResult) {
            return $cacheResult;
        }

        $refreshRequestCache = true;
        $symbol = $stockRequestDTO->companySymbol;
        $startDate = $stockRequestDTO->getStartDateImmutable();
        $endDate = $stockRequestDTO->getEndDateImmutable();

        $missingMonths = $this->historicalDataCache->findMissingMonths(
            $symbol,
            $this->generateMonthRange($startDate, $endDate)
        );

        if (count($missingMonths)) {
            $missingPeriodStart = $missingMonths[0]->modify('first day of this month');
            $missingPeriodEnd = array_reverse($missingMonths)[0]->modify('last day of this month');

            try {
                $missingData = $this->apiClient->fetchHistoricalData(
                    $symbol,
                    $missingPeriodStart,
                    $missingPeriodEnd,
                    $this->transformer
                );

                foreach ($this->segmentToMonths($missingData) as $month => $segmentData) {
                    $month = DateTimeImmutable::createFromFormat('Ymd', $month.'01')->setTime(0, 0);
                    $this->historicalDataCache->storeMonthlySegment($symbol, $month, $segmentData);
                }
            } catch (Exception $e) {
                $this->logger->error('Failed to fetch historical data', [
                    'symbol' => $symbol,
                    'from' => $missingPeriodStart->format(DateFormat::ISO_DATE),
                    'to' => $missingPeriodEnd->format(DateFormat::ISO_DATE),
                    'error' => $e->getMessage(),
                ]);
                $refreshRequestCache = false;
            }
        }

        $combinedHistoricalData = $this->combineMonthlyData($symbol, $startDate, $endDate);
        if ($refreshRequestCache) {
            $this->historicalDataCache->saveToRequestCache($stockRequestDTO, $combinedHistoricalData);
        }

        return $combinedHistoricalData;
    }

    /**
     * @param HistoryItemDTO[] $historicalData
     *
     * @return HistoryItemDTO[][]
     */
    private function segmentToMonths(array $historicalData): array
    {
        $segments = [];
        foreach ($historicalData as $historicalDataItem) {
            $monthKey = $historicalDataItem->getDateImmutable()->format('Ym');
            if (!array_key_exists($monthKey, $segments)) {
                $segments[$monthKey] = [];
            }
            $segments[$monthKey][] = $historicalDataItem;
        }

        return $segments;
    }

    private function combineMonthlyData(
        string $symbol,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate
    ): array {
        $months = $this->generateMonthRange($startDate, $endDate);
        $combinedData = [];

        foreach ($months as $month) {
            $monthlyData = $this->historicalDataCache->getMonthlySegment($symbol, $month);
            if ($monthlyData) {
                $filteredData = array_filter(
                    $monthlyData, fn(HistoryItemDTO $historyItemDTO) => $historyItemDTO->date >= $startDate->format(
                        DateFormat::ISO_DATE
                    ) && $historyItemDTO->date <= $endDate->format(DateFormat::ISO_DATE)
                );
                $combinedData = array_merge($combinedData, $filteredData);
            }
        }

        return $combinedData;
    }

    /**
     * @param DateTimeImmutable $start
     * @param DateTimeImmutable $end
     *
     * @return DateTimeImmutable[]
     */
    private function generateMonthRange(DateTimeImmutable $start, DateTimeImmutable $end): array
    {
        $months = [];
        $current = $start->modify('first day of this month');
        $lastMonth = $end->modify('first day of this month');

        while ($current <= $lastMonth) {
            $months[] = $current;
            $current = $current->modify('first day of next month');
        }

        return $months;
    }
}
