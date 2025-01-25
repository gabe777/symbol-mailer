<?php

declare(strict_types=1);

namespace App\Enum;

enum StockRequestParameters: string
{
    case COMPANY_SYMBOL = 'companySymbol';
    case START_DATE = 'startDate';
    case END_DATE = 'endDate';
    case EMAIL = 'email';
}
