<?php

namespace App\Tests\Unit\Validator;

use App\Validator\NotInFuture;
use App\Validator\NotInFutureValidator;
use DateTime;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class NotInFutureValidatorTest extends TestCase
{
    public function testValidate_valid()
    {
        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->never())->method('buildViolation');

        $constraint = new NotInFuture();

        $validator = new NotInFutureValidator();
        $validator->initialize($context);
        $validator->validate(DateTime::createFromFormat('', '')->format('Y-m-d'), $constraint); // Current day.
        $validator->validate('2025-01-20', $constraint);
        $validator->validate('2024-01-20', $constraint);
        $validator->validate('20240120', $constraint); // Only valid format should trigger violation.
    }

    public function testValidate_invalid()
    {
        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->expects($this->exactly(2))->method('addViolation');

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->exactly(2))->method('buildViolation')->willReturn($violationBuilder);

        $constraint = new NotInFuture();

        $validator = new NotInFutureValidator();
        $validator->initialize($context);
        $validator->validate(
            DateTime::createFromFormat('', '')->modify('+1 day')->format('Y-m-d'),
            $constraint
        ); // Current day + 1
        $validator->validate(
            DateTime::createFromFormat('', '')->modify('+1 year')->format('Y-m-d'),
            $constraint
        ); // Current day + 1 year
    }
}
