<?php

namespace App\Tests\Unit\Validator;

use App\Service\SymbolService;
use App\Validator\ValidSymbol;
use App\Validator\ValidSymbolValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class ValidSymbolValidatorTest extends TestCase
{

    public function testValidate_valid()
    {
        $testValue = 'testValue';
        $service = $this->createMock(SymbolService::class);
        $service->expects($this->once())->method('isValidSymbol')->with($testValue)->willReturn(true);

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->never())->method('buildViolation');

        $validator = new ValidSymbolValidator($service);
        $validator->initialize($context);
        $validator->validate($testValue, new ValidSymbol());
    }

    public function testValidate_invalid()
    {
        $testValue = 'testValue';
        $service = $this->createMock(SymbolService::class);
        $service->expects($this->once())->method('isValidSymbol')->with($testValue)->willReturn(false);

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->expects($this->once())->method('setParameter')->willReturn($violationBuilder);
        $violationBuilder->expects($this->once())->method('addViolation');

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->once())->method('buildViolation')->willReturn($violationBuilder);

        $validator = new ValidSymbolValidator($service);
        $validator->initialize($context);
        $validator->validate($testValue, new ValidSymbol());
    }
}
