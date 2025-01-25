<?php

declare(strict_types=1);

namespace App\Transformer;

use App\Constant\DateFormat;
use App\DTO\HistoryItemDTO;
use DateTimeImmutable;
use UnexpectedValueException;

class HistoryItemTransformer
{
    /**
     * Transforms the API response into a collection of HistoryItemDTOs.
     *
     * @param array $apiResponse
     * @return HistoryItemDTO[]
     */
    public function transform(array $apiResponse): array
    {
        if (!isset($apiResponse['chart']['result'][0]['timestamp'], $apiResponse['chart']['result'][0]['indicators']['quote'][0])) {
            throw new UnexpectedValueException('Invalid API response structure.');
        }

        $result = $apiResponse['chart']['result'][0];
        $timestamps = $result['timestamp'];
        $quotes = $result['indicators']['quote'][0];
        $symbol = $result['meta']['symbol'];

        $historyItems = [];
        foreach ($timestamps as $index => $timestamp) {

            $historyItems[] = new HistoryItemDTO(
                $symbol,
                (new DateTimeImmutable())->setTimestamp($timestamp)->setTime(0, 0)->format(DateFormat::ISO_DATE),
                (float)$quotes['open'][$index],
                (float)$quotes['high'][$index],
                (float)$quotes['low'][$index],
                (float)$quotes['close'][$index],
                (float)$quotes['volume'][$index]
            );
        }

        return $historyItems;
    }
}
