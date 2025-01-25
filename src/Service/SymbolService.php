<?php

declare(strict_types=1);

namespace App\Service;

use App\Adapter\StorageAdapterInterface;
use App\ApiClient\SymbolInfoApiClientInterface;
use App\DTO\SymbolInfoDTO;

class SymbolService
{

    public function __construct(
        private readonly StorageAdapterInterface $storageAdapter,
        private readonly SymbolInfoApiClientInterface $apiClient
    ) {
    }

    public function isValidSymbol(string $symbol): bool
    {
        return $this->getSymbolInfo($symbol) !== null;
    }

    public function getSymbolInfo(string $symbol): ?SymbolInfoDTO
    {
        $symbols = $this->storageAdapter->getSymbols();

        if ($symbols === null) {
            $symbols = $this->apiClient->fetchSymbolInfo();
            $this->storageAdapter->saveSymbols($symbols);
        }

        foreach ($symbols as $item) {
            if (strcasecmp($item->symbol, $symbol) === 0) {
                return $item;
            }
        }

        return null;
    }
}
