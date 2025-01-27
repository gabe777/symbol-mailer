<?php

declare(strict_types=1);

namespace App\ApiClient;

use App\DTO\HistoryItemDTO;
use App\Transformer\HistoryItemTransformer;
use DateTimeImmutable;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

interface HistoricalDataApiClientInterface
{
    /**
     * Fetches the symbols from the external API.
     *
     * @return HistoryItemDTO[]
     *
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function fetchHistoricalData(
        string $symbol,
        DateTimeImmutable $periodStart,
        DateTimeImmutable $periodEnd,
        HistoryItemTransformer $historyItemTransformer
    ): array;
}