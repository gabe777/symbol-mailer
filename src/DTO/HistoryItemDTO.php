<?php

declare(strict_types=1);

namespace App\DTO;

use App\Constant\DateFormat;
use DateTimeImmutable;

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

    public function getDateImmutable(bool $withZeroTime = true): DateTimeImmutable
    {
        $immutable = DateTimeImmutable::createFromFormat(DateFormat::ISO_DATE, $this->date);
        if ($withZeroTime) {
            $immutable = $immutable->setTime(0, 0);
        }

        return $immutable;
    }
}