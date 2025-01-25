<?php

declare(strict_types=1);

namespace App\Factory;

use App\DTO\StockRequestDTO;
use App\Enum\StockRequestParameters;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Exception\MissingConstructorArgumentsException;
use Symfony\Component\Serializer\SerializerInterface;

readonly class StockRequestDTOFactory
{
    public function __construct(private SerializerInterface $serializer)
    {
    }
    public function createFromRequest(Request $request): StockRequestDTO
    {
        try {
            if (!empty($request->getContent())) {
                $content = json_decode($request->getContent(), true);
                $content[StockRequestParameters::COMPANY_SYMBOL->value] =
                    $this->getRequestParameter($request, StockRequestParameters::COMPANY_SYMBOL);
                $dto = $this->serializer->denormalize($content, StockRequestDTO::class);
            } else {
                $dto = new StockRequestDTO(
                    $this->getRequestParameter($request, StockRequestParameters::COMPANY_SYMBOL),
                    $this->getRequestParameter($request, StockRequestParameters::START_DATE),
                    $this->getRequestParameter($request, StockRequestParameters::END_DATE),
                    $this->getRequestParameter($request, StockRequestParameters::EMAIL)
                );
            }
        } catch (\Exception $e) {
            $message = 'Error during request parameter processing: ' . $e->getMessage();
            if ($e instanceof InvalidArgumentException) {
                $message = $e->getMessage();
            } elseif ($e instanceof MissingConstructorArgumentsException) {
                $message = 'Missing required parameter in payload!';
            }
            throw new InvalidArgumentException($message, $e->getCode(), $e);
        }

        return $dto;
    }

    private function getRequestParameter(Request $request, StockRequestParameters $parameter): string
    {
        $value = $request->get($parameter->value);
        if ($value === null) {
            throw new InvalidArgumentException('Missing required parameter: '.$parameter->value);
        }

        return $value;
    }
}
