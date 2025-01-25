<?php

declare(strict_types=1);

namespace App\Adapter;

use App\DTO\SymbolInfoDTO;

interface StorageAdapterInterface
{
    /**
     * @param SymbolInfoDTO[] $symbols
     * @return void
     */
    public function saveSymbols(array $symbols): void;

    /**
     * @return SymbolInfoDTO[]|null
     */
    public function getSymbols(): ?array;
}