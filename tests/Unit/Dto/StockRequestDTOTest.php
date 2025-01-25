<?php

namespace App\Tests\Unit\Dto;

use App\Dto\StockRequest;
use App\Service\SymbolService;
use App\Validator\ValidSymbol;
use App\Validator\ValidSymbolValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Date;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertInstanceOf;

class StockRequestTest extends TestCase
{
    private ValidatorInterface $validator;
    private SymbolService|MockObject $symbolService;

    protected function getConstraintValidatorFactory(): ConstraintValidatorFactory
    {
        return new class($this->symbolService) extends ConstraintValidatorFactory {

            public function __construct(private readonly SymbolService $symbolService)
            {
                parent::__construct([]);
            }

            public function getInstance(Constraint $constraint): ConstraintValidatorInterface
            {
                if ($constraint instanceof ValidSymbol) {
                    return new ValidSymbolValidator($this->symbolService);
                }

                return parent::getInstance($constraint);
            }
        };
    }

    protected function setUp(): void
    {
        $this->symbolService = $this->createMock(SymbolService::class);

        $this->validator = Validation::createValidatorBuilder()->enableAttributeMapping(
        )->setConstraintValidatorFactory($this->getConstraintValidatorFactory())->getValidator();
    }

    public function test__construct()
    {
        $dto = new StockRequest('companySymbol', '2025-01-01', '2025-01-02', 'test@test.test');
        assertEquals('companySymbol', $dto->companySymbol);
        assertEquals('2025-01-01', $dto->startDate);
        assertEquals('2025-01-02', $dto->endDate);
        assertEquals('test@test.test', $dto->email);
    }

    public function testValidation_valid()
    {
        $this->symbolService->method('isValidSymbol')->willReturn(true);
        $dto = new StockRequest('companySymbol', '2025-01-01', '2025-01-02', 'test@test.test');
        $violations = $this->validator->validate($dto);
        assertEquals(0, $violations->count());
    }

    public function testValidation_invalid_symbolAndFormats()
    {
        $this->symbolService->method('isValidSymbol')->willReturn(false);
        $dto = new StockRequest('companySymbol', '20250101', '20250102', 'testtest.test');
        $violations = $this->validator->validate($dto);
        assertEquals(4, $violations->count());
        assertInstanceOf(ValidSymbol::class, $violations->get(0)->getConstraint());
        assertInstanceOf(Date::class, $violations->get(1)->getConstraint());
        assertInstanceOf(Date::class, $violations->get(2)->getConstraint());
        assertInstanceOf(Email::class, $violations->get(3)->getConstraint());
    }

    public function testValidation_invalid_endDate()
    {
        $this->symbolService->method('isValidSymbol')->willReturn(true);
        $dto = new StockRequest('companySymbol', '2025-01-02', '2025-01-01', 'test@test.test');
        $violations = $this->validator->validate($dto);
        assertEquals(1, $violations->count());
        assertInstanceOf(GreaterThanOrEqual::class, $violations->get(0)->getConstraint());
    }
}
