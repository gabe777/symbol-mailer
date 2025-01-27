<?php

declare(strict_types=1);

namespace App\DTO;

use App\Constant\DateFormat;
use App\Validator\NotInFuture;
use App\Validator\ValidSymbol;
use DateTimeImmutable;
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

    public function getStartDateImmutable(bool $withZeroTime = true): DateTimeImmutable
    {
        return $this->getDateImmutable($this->startDate, $withZeroTime);
    }

    public function getEndDateImmutable(bool $withZeroTime = true): DateTimeImmutable
    {
        return $this->getDateImmutable($this->endDate, $withZeroTime);
    }

    private function getDateImmutable(string $date, bool $withZeroTime): DateTimeImmutable
    {
        $immutable = DateTimeImmutable::createFromFormat(DateFormat::ISO_DATE, $date);
        if ($withZeroTime) {
            $immutable = $immutable->setTime(0, 0);
        }

        return $immutable;
    }
}
