<?php

declare(strict_types=1);

namespace App\ApiClient\HistoricalData;

use App\ApiClient\HistoricalDataApiClientInterface;
use App\Constant\DateFormat;
use App\DTO\HistoryItemDTO;
use App\DTO\StockRequestDTO;
use App\Transformer\HistoryItemTransformer;
use DateTimeImmutable;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class YahooFinanceApiClient implements HistoricalDataApiClientInterface
{
    private const string BASE_URL = 'https://yh-finance.p.rapidapi.com/stock/v3/';
    private const int MAX_RETRIES = 3;
    private const int INITIAL_RETRY_DELAY = 1000;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $apiKey,
        private readonly string $apiHost
    ) {
    }

    /**
     * Fetches the symbols from the external API.
     *
     * @return HistoryItemDTO[]
     *
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function fetchHistoricalData(
        StockRequestDTO $stockRequestDTO,
        HistoryItemTransformer $historyItemTransformer
    ): array {
        $requestHeaders = [
            'X-RapidAPI-Key' => $this->apiKey,
            'X-RapidAPI-Host' => $this->apiHost,
        ];

        $query = [
            'symbol' => $stockRequestDTO->companySymbol,
            'interval' => '1d',
            'period1' => DateTimeImmutable::createFromFormat(
                DateFormat::ISO_DATE,
                $stockRequestDTO->startDate
            )->setTime(0, 0)->getTimestamp(),
            'period2' => DateTimeImmutable::createFromFormat(
                DateFormat::ISO_DATE,
                $stockRequestDTO->endDate
            )->setTime(0, 0)->getTimestamp(),
            'events' => 'history',
        ];

        $retryCount = 0;
        $retryDelay = self::INITIAL_RETRY_DELAY;

        while ($retryCount < self::MAX_RETRIES) {
            try {
                $response = $this->httpClient->request(
                    Request::METHOD_GET, self::BASE_URL.'get-chart', [
                        'headers' => $requestHeaders,
                        'query' => $query,
                    ]
                );

                $statusCode = $response->getStatusCode();

                if ($statusCode >= Response::HTTP_OK && $statusCode < Response::HTTP_MULTIPLE_CHOICES) {
                    return $historyItemTransformer->transform($response->toArray());
                }

                if ($statusCode === Response::HTTP_TOO_MANY_REQUESTS) {
                    $this->logger->warning("Rate limit exceeded. Retrying...");
                } elseif ($statusCode >= Response::HTTP_INTERNAL_SERVER_ERROR) {
                    $this->logger->error("Server error (HTTP $statusCode). Retrying...");
                } else {
                    $this->logger->error("Client error (HTTP $statusCode). Aborting.");

                    return [];
                }
            } catch (TransportExceptionInterface $e) {
                $this->logger->error("Network error: {$e->getMessage()}. Retrying...");
            } catch (DecodingExceptionInterface $e) {
                $this->logger->error("Response decoding error: {$e->getMessage()}. Aborting.");

                return [];
            } catch (Exception $e) {
                $this->logger->critical("Unexpected error: {$e->getMessage()}. Aborting.");

                return [];
            }

            $retryCount++;
            usleep($retryDelay * 1000);
            $retryDelay *= 2;
        }

        return [];
    }
}