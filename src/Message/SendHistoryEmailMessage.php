<?php

declare(strict_types=1);

namespace App\Message;

use App\DTO\StockRequestDTO;

readonly class SendHistoryEmailMessage
{
    /**
     * @param StockRequestDTO $stockRequestDTO
     */
    public function __construct(
        public StockRequestDTO $stockRequestDTO
    ) {
    }
}
