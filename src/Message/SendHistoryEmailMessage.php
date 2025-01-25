<?php

declare(strict_types=1);

namespace App\Message;

use App\DTO\HistoryItemDTO;

readonly class SendHistoryEmailMessage
{
    /**
     * @param string $companyName
     * @param string $startDate
     * @param string $endDate
     * @param string $email
     * @param HistoryItemDTO[] $historicalData
     */
    public function __construct(
        public string $companyName,
        public string $startDate,
        public string $endDate,
        public string $email,
        public array $historicalData
    ) {
    }
}
