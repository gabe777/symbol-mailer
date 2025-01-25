<?php

declare(strict_types=1);

namespace App\Service;

use App\ApiClient\HistoricalDataApiClientInterface;
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
        return $this->apiClient->fetchHistoricalData($stockRequestDTO, $this->transformer);
    }
}
