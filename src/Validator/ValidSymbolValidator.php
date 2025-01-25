<?php

declare(strict_types=1);

namespace App\Validator;

use App\Service\SymbolService;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ValidSymbolValidator extends ConstraintValidator
{

    public function __construct(private readonly SymbolService $symbolService)
    {
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$this->symbolService->isValidSymbol($value)) {
            $this->context->buildViolation($constraint->message)->setParameter('{{ value }}', $value)->addViolation();
        }
    }
}
