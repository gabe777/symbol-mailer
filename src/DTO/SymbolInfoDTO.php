<?php

declare(strict_types=1);

namespace App\DTO;

readonly class SymbolInfoDTO
{
    public function __construct(
        public string $symbol,
        public string $companyName
    ) {
    }
}