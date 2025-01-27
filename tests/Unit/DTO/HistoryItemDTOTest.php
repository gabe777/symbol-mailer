<?php

namespace App\Tests\Unit\DTO;

use App\DTO\HistoryItemDTO;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

use function PHPUnit\Framework\assertEquals;

class HistoryItemDTOTest extends TestCase
{
    public function testGetDateImmutable()
    {
        $dto = new HistoryItemDTO(
            'symbol', '2025-01-01', 1, 1, 1, 1, 1
        );

        assertEquals(DateTimeImmutable::createFromFormat('YmdHis', '20250101000000'), $dto->getDateImmutable());
    }
}
