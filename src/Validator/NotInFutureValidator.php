<?php

declare(strict_types=1);

namespace App\Validator;

use App\Constant\DateFormat;
use DateTimeImmutable;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class NotInFutureValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        $currentDate = DateTimeImmutable::createFromFormat('', '')->setTime(0, 0);
        $valueDate = (DateTimeImmutable::createFromFormat(DateFormat::ISO_DATE, $value) ?: null)?->setTime(0, 0);
        if ($currentDate < $valueDate) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
