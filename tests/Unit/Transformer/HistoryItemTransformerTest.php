<?php

namespace App\Tests\Unit\Transformer;

use App\DTO\HistoryItemDTO;
use App\Transformer\HistoryItemTransformer;
use PHPUnit\Framework\TestCase;

use UnexpectedValueException;

use function PHPUnit\Framework\assertContainsOnlyInstancesOf;
use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertIsArray;

class HistoryItemTransformerTest extends TestCase
{

    public function testTransform_validApiResponse()
    {
        $transformer = new HistoryItemTransformer();
        $result = $transformer->transform($this->getSuccessfulResponseArray());
        assertIsArray($result);
        assertCount(2, $result);
        assertContainsOnlyInstancesOf(HistoryItemDTO::class, $result);

        $firstResult = $result[0];
        $secondResult = $result[1];

        assertEquals('MSTR', $firstResult->symbol);
        assertEquals('MSTR', $secondResult->symbol);
        assertEquals(array_values(array_map(fn($item) => $item[0], $this->getOhlcvResponseArray())),
            [$firstResult->open, $firstResult->high, $firstResult->low, $firstResult->close, $firstResult->volume]);
        assertEquals(array_values(array_map(fn($item) => $item[1], $this->getOhlcvResponseArray())),
            [$secondResult->open, $secondResult->high, $secondResult->low, $secondResult->close, $secondResult->volume]
        );
    }

    public function testTransform_emptyApiResponse()
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid API response structure.');

        $transformer = new HistoryItemTransformer();
        $transformer->transform([]);
    }

    protected function getSuccessfulResponseArray(): array
    {
        return [
            'chart' => [
                'result' => [
                    [
                        'meta' => [
                            'symbol' => 'MSTR',
                        ],
                        'timestamp' => [1736031600, 1736118000],
                        'indicators' => [
                            'quote' => [
                                $this->getOhlcvResponseArray(),
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function getOhlcvResponseArray(): array
    {
        return [
            'open' => [123, 321],
            'high' => [789, 987],
            'low' => [100, 200],
            'close' => [456, 654],
            'volume' => [1000000, 2000000],
        ];
    }
}
