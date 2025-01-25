<?php

declare(strict_types=1);

namespace App\Service;

use App\ApiClient\HistoricalDataApiClientInterface;
use App\Cache\HistoricalDataCache;
use App\DTO\HistoryItemDTO;
use App\DTO\StockRequestDTO;
use App\Transformer\HistoryItemTransformer;
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
        private readonly HistoricalDataCache $historicalDataCache
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
     */
    public function getHistoricalData(StockRequestDTO $stockRequestDTO): array
    {
        $cacheResult = $this->historicalDataCache->getFromRequestCache($stockRequestDTO);
        if (null !== $cacheResult) {
            return $cacheResult;
        }
        $apiResult = $this->apiClient->fetchHistoricalData($stockRequestDTO, $this->transformer);
        $this->historicalDataCache->saveToRequestCache($stockRequestDTO, $apiResult);

        return $apiResult;
    }
}
