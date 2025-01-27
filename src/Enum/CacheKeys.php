<?php

namespace App\Enum;

enum CacheKeys: string
{
    case CACHE_KEY_COMPANY_SYMBOLS = 'company_symbols';
    case CACHE_KEY_MONTH = 'historical_data_{symbol}_{year}_{month}';
    case CACHE_KEY_REQUEST = 'request_{symbol}_{from}-{to}';

    public function generateKey(array $params): string
    {
        $key = $this->value;
        foreach ($params as $param => $value) {
            $key = str_replace('{'.$param.'}', $value, $key);
        }

        return $key;
    }
}