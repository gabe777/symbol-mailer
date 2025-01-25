<?php

declare(strict_types=1);

namespace App\DTO;

use App\Validator\NotInFuture;
use App\Validator\ValidSymbol;
use Symfony\Component\Validator\Constraints as Assert;

readonly class StockRequestDTO
{

    public function __construct(
        #[Assert\NotBlank]
        #[ValidSymbol]
        public string $companySymbol,

        #[Assert\NotBlank]
        #[Assert\Date]
        #[Assert\Regex(pattern: '/^\d{4}-\d{2}-\d{2}$/', message: 'StartDate must be in the format Y-m-d.')]
        #[NotInFuture]
        public string $startDate,

        #[Assert\NotBlank]
        #[Assert\Date]
        #[Assert\Regex(pattern: '/^\d{4}-\d{2}-\d{2}$/', message: 'EndDate must be in the format Y-m-d.')]
        #[NotInFuture]
        #[Assert\GreaterThanOrEqual(propertyPath: 'startDate', message: 'EndDate should be greater than StartDate ({{ compared_value }})')]
        public string $endDate,

        #[Assert\NotBlank]
        #[Assert\Email]
        public string $email
    ) {
    }
}
