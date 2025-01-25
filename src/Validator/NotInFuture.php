<?php

declare(strict_types=1);

namespace App\Validator;

use Attribute;
use Symfony\Component\Validator\Constraint;

#[Attribute]
class NotInFuture extends Constraint
{
    public string $message = 'Start and End dates must be less than or equal as today.';
}
