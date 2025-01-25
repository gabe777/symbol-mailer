<?php

declare(strict_types=1);

namespace App\DTO;

readonly class HistoryItemDTO
{
    public function __construct(
        public string $symbol,
        public string $date,
        public float $open,
        public float $high,
        public float $low,
        public float $close,
        public float $volume,
    ) {
    }
}