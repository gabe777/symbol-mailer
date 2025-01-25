<?php

declare(strict_types=1);

namespace App\ApiClient\SymbolInfo;

use App\ApiClient\SymbolInfoApiClientInterface;
use App\Constant\SymbolInfoApiResponseEntityKeys;
use App\DTO\SymbolInfoDTO;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DataHubApiClient implements SymbolInfoApiClientInterface
{
    private const string SYMBOL_API_URL = 'https://pkgstore.datahub.io/core/nasdaq-listings/nasdaq-listed_json/data/a5bc7580d6176d60ac0b2142ca8d7df6/nasdaq-listed_json.json';

    public function __construct(private readonly HttpClientInterface $httpClient)
    {
    }

    /**
     * Fetches the symbols from the external API.
     *
     * @return SymbolInfoDTO[]
     *
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function fetchSymbolInfo(): array
    {
        // @todo Error handling, retry, etc.
        $response = $this->httpClient->request(Request::METHOD_GET, self::SYMBOL_API_URL);
        $data = $response->toArray();

        return array_map(
            fn($item) => new SymbolInfoDTO(
                $item[SymbolInfoApiResponseEntityKeys::SYMBOL], $item[SymbolInfoApiResponseEntityKeys::COMPANY_NAME]
            ),
            $data
        );
    }
}