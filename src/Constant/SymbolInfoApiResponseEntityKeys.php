<?php

declare(strict_types=1);

namespace App\Constant;

/**
 * An arbitrary subset of entity keys of the response of api call to https://pkgstore.datahub.io/core/nasdaq-listings/nasdaq-listed_json
 */
class SymbolInfoApiResponseEntityKeys
{
    public const string SYMBOL = 'Symbol';
    public const string COMPANY_NAME = 'Company Name';
}
