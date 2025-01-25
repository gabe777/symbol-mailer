<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Factory\StockRequestDTOFactory;
use App\Message\SendHistoryEmailMessage;
use App\Service\HistoricalDataService;
use App\Service\SymbolService;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/stock/')]
class StockController extends AbstractController
{
    public function __construct(
        private readonly StockRequestDTOFactory $factory,
        private readonly SymbolService $symbolService,
        private readonly HistoricalDataService $historicalDataService,
        private readonly ValidatorInterface $validator,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    #[Route('{companySymbol}/history', name: 'app_api_v1_stock_history', methods: [
        Request::METHOD_POST,
    ])]
    #[OA\Post(path: '/api/v1/stock/{companySymbol}/history', description: 'The endpoint provides daily ohlcv historical data for a given symbol within a given period, designated by startDate and endDate. The email address is required, so that the application can send the historical data in a csv file to it.', summary: 'Retrieves historical data for the given symbol and sends it to the given email.', security: [
        ['api_key_query' => []],
        ['api_key_header' => []],
    ], requestBody: new OA\RequestBody(
        description: 'Request payload for stock data', required: true, content: new OA\JsonContent(
        properties: [
            new OA\Property(
                property: 'startDate',
                description: 'The first day from which the data is retrieved',
                type: 'string',
                format: 'date'
            ),
            new OA\Property(
                property: 'endDate',
                description: 'The last day from which the data is retrieved',
                type: 'string',
                format: 'date'
            ),
            new OA\Property(property: 'email', description: 'Recipient email', type: 'string', format: 'email'),
        ], type: 'object'
    )
    ), parameters: [
        new OA\Parameter(
            name: 'companySymbol',
            description: 'The stock symbol (e.g., AAPL, MSFT)',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'string')
        ),
    ], responses: [
        new OA\Response(
            response: 200, description: 'Historical data processed', content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'historicalQuotes',
                    description: 'Array of historical stock data',
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(
                                property: 'symbol', description: 'Stock symbol', type: 'string', example: 'AMWD'
                            ),
                            new OA\Property(
                                property: 'date',
                                description: 'Date and time of the stock data',
                                type: 'string',
                                format: 'date-time',
                                example: '2024-12-31T15:30:00+01:00'
                            ),
                            new OA\Property(
                                property: 'open',
                                description: 'Opening price',
                                type: 'number',
                                format: 'float',
                                example: 80.27
                            ),
                            new OA\Property(
                                property: 'high',
                                description: 'Highest price of the day',
                                type: 'number',
                                format: 'float',
                                example: 80.77
                            ),
                            new OA\Property(
                                property: 'low',
                                description: 'Lowest price of the day',
                                type: 'number',
                                format: 'float',
                                example: 79.5
                            ),
                            new OA\Property(
                                property: 'close',
                                description: 'Closing price',
                                type: 'number',
                                format: 'float',
                                example: 79.53
                            ),
                            new OA\Property(
                                property: 'volume',
                                description: 'Trading volume for the day',
                                type: 'integer',
                                example: 114500
                            ),
                        ], type: 'object'
                    )
                ),
            ], type: 'object'
        )
        ),
        new OA\Response(
            response: 400, description: 'Invalid input data', content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'errors', type: 'array', items: new OA\Items(type: 'string')
                ),
            ], type: 'object'
        )
        ),
        new OA\Response(
            response: 401, description: 'Unauthorized', content: new OA\JsonContent()
        ),
    ])]
    public function history(Request $request): Response
    {
        try {
            $stockRequestDTO = $this->factory->createFromRequest($request);
            $violations = $this->validator->validate($stockRequestDTO);

            if (count($violations) > 0) {
                $errors = [];
                foreach ($violations as $violation) {
                    $errors[] = $violation->getMessage();
                }

                return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
            }

            $historicalData = $this->historicalDataService->getHistoricalData($stockRequestDTO);

            $this->messageBus->dispatch(
                new SendHistoryEmailMessage(
                    $this->symbolService->getSymbolInfo($stockRequestDTO->companySymbol)->companyName,
                    $stockRequestDTO->startDate,
                    $stockRequestDTO->endDate,
                    $stockRequestDTO->email,
                    $historicalData
                )
            );

            return $this->json(
                ['historicalQuotes' => $historicalData],
                Response::HTTP_OK
            );
        } catch (InvalidArgumentException $e) {
            return $this->json(['errors' => [$e->getMessage()]], Response::HTTP_BAD_REQUEST);
        }
    }
}
