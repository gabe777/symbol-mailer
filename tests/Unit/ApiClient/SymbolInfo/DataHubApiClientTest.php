<?php

namespace App\Tests\Unit\ApiClient\SymbolInfo;

use App\ApiClient\SymbolInfo\DataHubApiClient;
use App\DTO\SymbolInfoDTO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

use function PHPUnit\Framework\assertContainsOnlyInstancesOf;
use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertInstanceOf;
use function PHPUnit\Framework\assertIsArray;

class DataHubApiClientTest extends TestCase
{
    public function test__construct()
    {
        $client = new DataHubApiClient($this->createMock(HttpClientInterface::class));
        assertInstanceOf(DataHubApiClient::class, $client);
    }

    public function testFetchSymbolInfo()
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('toArray')->willReturn($this->getTestResponse());

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->once())->method('request')->with(Request::METHOD_GET, 'https://pkgstore.datahub.io/core/nasdaq-listings/nasdaq-listed_json/data/a5bc7580d6176d60ac0b2142ca8d7df6/nasdaq-listed_json.json')->willReturn($response);

        $client = new DataHubApiClient($httpClient);
        $result = $client->fetchSymbolInfo();
        assertIsArray($result);
        assertCount(2, $result);
        assertContainsOnlyInstancesOf(SymbolInfoDTO::class, $result);
        assertEquals('Mock Inc.', $result[1]->companyName);
        assertEquals('MCK', $result[1]->symbol);
    }

    protected function getTestResponse(): array
    {
        return [
            [
                'Company Name' => 'Test Ltd.',
                'Symbol' => 'TST',
                'Unprocessed key' => '123'
            ],

            [
                'Company Name' => 'Mock Inc.',
                'Symbol' => 'MCK',
                'Unprocessed key' => '456'
            ]
        ];
    }
}
