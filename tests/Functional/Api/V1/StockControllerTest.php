<?php

namespace App\Tests\Functional\Api\V1;

use App\ApiClient\HistoricalData\YahooFinanceApiClient;
use App\DTO\HistoryItemDTO;
use App\DTO\SymbolInfoDTO;
use App\Message\SendHistoryEmailMessage;
use App\Service\SymbolService;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

use function PHPUnit\Framework\assertArrayHasKey;
use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertInstanceOf;
use function PHPUnit\Framework\assertIsArray;

class StockControllerTest extends WebTestCase
{
    private const string ENDPOINT = '/api/v1/stock/';

    private KernelBrowser $client;
    private SymbolService $symbolService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();

        $this->symbolService = $this->createMock(SymbolService::class);
        self::getContainer()->set(SymbolService::class, $this->symbolService);
    }

    public function testUnauthorized(): void
    {
        $this->client->request(
            Request::METHOD_POST,
            self::ENDPOINT.'AAPL/history',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([])
        );
        $response = $this->client->getResponse();
        assertEquals(401, $response->getStatusCode());
    }

    public function testValidRequest(): void
    {
        $payload = [
            'startDate' => '2023-01-01',
            'endDate' => '2023-01-31',
            'email' => 'test@example.com',
        ];

        $transformedYfApiResponse = [
            new HistoryItemDTO(
                'AAPL',
                DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2023-01-01 00:00:00')->format('Y-m-d'),
                123,
                789,
                100,
                456,
                10000
            ),
            new HistoryItemDTO(
                'AAPL',
                DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2023-01-02 00:00:00')->format('Y-m-d'),
                321,
                987,
                200,
                654,
                20000
            ),
        ];

        $yfApiClient = $this->createMock(YahooFinanceApiClient::class);
        $yfApiClient->expects($this->once())->method('fetchHistoricalData')->willReturn($transformedYfApiResponse);

        self::getContainer()->set(YahooFinanceApiClient::class, $yfApiClient);

        $this->symbolService->method('isValidSymbol')->willReturn(true);
        $this->symbolService->method('getSymbolInfo')->willReturn(new SymbolInfoDTO('AAPL', 'Apple Inc.'));

        $this->client->request(
            Request::METHOD_POST,
            self::ENDPOINT.'AAPL/history?api_key=1231231233',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($response->getContent(), true);
        assertArrayHasKey('historicalQuotes', $responseData);
        assertIsArray($responseData['historicalQuotes']);
        assertEquals($transformedYfApiResponse[0]->volume, $responseData['historicalQuotes'][0]['volume']);
        assertEquals($transformedYfApiResponse[1]->volume, $responseData['historicalQuotes'][1]['volume']);

        $messengerTransport = self::getContainer()->get('messenger.transport.async');
        assertCount(1, $messengerTransport->getSent());

        $message = $messengerTransport->getSent()[0]->getMessage();
        assertInstanceOf(SendHistoryEmailMessage::class, $message);
        assertEquals('Apple Inc.', $message->companyName);
        assertEquals('2023-01-01', $message->startDate);
        assertEquals('2023-01-31', $message->endDate);
        assertEquals('test@example.com', $message->email);
        assertEquals($transformedYfApiResponse, $message->historicalData);
    }

    public function testInvalidCompanySymbol(): void
    {
        $payload = [
            'startDate' => '2023-01-01',
            'endDate' => '2023-01-31',
            'email' => 'test@example.com',
        ];

        $this->client->request(
            Request::METHOD_POST,
            self::ENDPOINT.'INVALID/history?api_key=1231231233',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $response = $this->client->getResponse();
        $this->assertResponseStatusCodeSame(400);

        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $responseData);
        $this->assertStringContainsString('The symbol "INVALID" is invalid.', $responseData['errors'][0]);
    }

    public function testInvalidDates(): void
    {
        $payload = [
            'startDate' => '2023-01-31',
            'endDate' => '2023-01-01',
            'email' => 'test@example.com',
        ];

        $this->symbolService->method('isValidSymbol')->willReturn(true);

        $this->client->request(
            Request::METHOD_POST,
            self::ENDPOINT.'AAPL/history?api_key=1231231233',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $response = $this->client->getResponse();
        $this->assertResponseStatusCodeSame(400);

        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $responseData);
        $this->assertStringContainsString(
            'EndDate should be greater than StartDate ("2023-01-31")',
            $responseData['errors'][0]
        );
    }

    public function testInvalidEmail(): void
    {
        $payload = [
            'startDate' => '2023-01-01',
            'endDate' => '2023-01-31',
            'email' => 'invalid-email',
        ];

        $this->symbolService->method('isValidSymbol')->willReturn(true);

        $this->client->request(
            Request::METHOD_POST,
            self::ENDPOINT.'AAPL/history?api_key=1231231233',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $response = $this->client->getResponse();
        $this->assertResponseStatusCodeSame(400);

        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $responseData);
        $this->assertStringContainsString('This value is not a valid email address', $responseData['errors'][0]);
    }

    public function testMissingRequiredFields(): void
    {
        $payload = [
            'test' => 'testValue',
        ];

        $this->client->request(
            Request::METHOD_POST,
            self::ENDPOINT.'AAPL/history?api_key=1231231233',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $response = $this->client->getResponse();
        $this->assertResponseStatusCodeSame(400);

        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $responseData);
        $this->assertCount(1, $responseData['errors']);
    }
}
