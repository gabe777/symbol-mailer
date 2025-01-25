<?php

declare(strict_types=1);

namespace App\Validator;

use Attribute;
use Symfony\Component\Validator\Constraint;

#[Attribute]
class ValidSymbol extends Constraint
{
    public string $message = 'The symbol "{{ value }}" is invalid.';
}
