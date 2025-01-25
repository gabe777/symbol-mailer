<?php

declare(strict_types=1);

namespace App\ApiClient;

use App\DTO\SymbolInfoDTO;

interface SymbolInfoApiClientInterface
{
    /**
     * Fetches the symbols from the external API.
     *
     * @return SymbolInfoDTO[]
     */
    public function fetchSymbolInfo(): array;
}