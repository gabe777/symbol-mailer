<?php

namespace App\Tests\Unit\DTO;

use App\DTO\StockRequestDTO;
use App\Service\SymbolService;
use App\Validator\NotInFuture;
use App\Validator\ValidSymbol;
use App\Validator\ValidSymbolValidator;
use DateTime;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Date;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertInstanceOf;

class StockRequestDTOTest extends TestCase
{
    private ValidatorInterface $validator;
    private SymbolService|MockObject $symbolService;

    protected function getConstraintValidatorFactory(): ConstraintValidatorFactory
    {
        return new class($this->symbolService) extends ConstraintValidatorFactory {

            public function __construct(private readonly SymbolService $symbolService)
            {
                parent::__construct();
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
        $dto = new StockRequestDTO('companySymbol', '2025-01-01', '2025-01-02', 'test@test.test');
        assertEquals('companySymbol', $dto->companySymbol);
        assertEquals('2025-01-01', $dto->startDate);
        assertEquals('2025-01-02', $dto->endDate);
        assertEquals('test@test.test', $dto->email);
    }

    public function testValidation_valid()
    {
        $this->symbolService->method('isValidSymbol')->willReturn(true);
        $dto = new StockRequestDTO('companySymbol', '2025-01-01', '2025-01-02', 'test@test.test');
        $violations = $this->validator->validate($dto);
        assertEquals(0, $violations->count());
    }

    public function testValidation_invalid_symbolAndFormats()
    {
        $this->symbolService->method('isValidSymbol')->willReturn(false);
        $dto = new StockRequestDTO('companySymbol', '20250101', '20250102', 'testtest.test');
        $violations = $this->validator->validate($dto);

        assertEquals(6, $violations->count());
        assertInstanceOf(ValidSymbol::class, $violations->get(0)->getConstraint());
        assertInstanceOf(Date::class, $violations->get(1)->getConstraint());
        assertInstanceOf(Regex::class, $violations->get(2)->getConstraint());
        assertInstanceOf(Date::class, $violations->get(3)->getConstraint());
        assertInstanceOf(Regex::class, $violations->get(4)->getConstraint());
        assertInstanceOf(Email::class, $violations->get(5)->getConstraint());


        $dto = new StockRequestDTO('companySymbol', '2025-01-01 00:00:00', '2025-01-01T00:00:00', 'test@test.test');
        $violations = $this->validator->validate($dto);
        assertEquals(5, $violations->count());
        assertInstanceOf(Date::class, $violations->get(1)->getConstraint());
        assertInstanceOf(Regex::class, $violations->get(2)->getConstraint());
        assertInstanceOf(Date::class, $violations->get(3)->getConstraint());
        assertInstanceOf(Regex::class, $violations->get(4)->getConstraint());
    }

    public function testValidation_invalid_endDateOlder()
    {
        $this->symbolService->method('isValidSymbol')->willReturn(true);
        $dto = new StockRequestDTO('companySymbol', '2025-01-02', '2025-01-01', 'test@test.test');
        $violations = $this->validator->validate($dto);
        assertEquals(1, $violations->count());
        assertInstanceOf(GreaterThanOrEqual::class, $violations->get(0)->getConstraint());
    }

    public function testValidation_invalid_tooLate()
    {
        $date = DateTime::createFromFormat('', '');

        $this->symbolService->method('isValidSymbol')->willReturn(true);
        $dto = new StockRequestDTO(
            'companySymbol',
            $date->modify('+1 day')->format('Y-m-d'),
            $date->modify('+1 day')->format('Y-m-d'),
            'test@test.test'
        );
        $violations = $this->validator->validate($dto);
        assertEquals(2, $violations->count());
        assertInstanceOf(NotInFuture::class, $violations->get(0)->getConstraint());
        assertEquals('startDate', $violations->get(0)->getPropertyPath());
        assertInstanceOf(NotInFuture::class, $violations->get(1)->getConstraint());
        assertEquals('endDate', $violations->get(1)->getPropertyPath());
    }

    public function testGetDateImmutable()
    {
        $dto = new StockRequestDTO(
            'symbol', '2025-01-01', '2025-01-02', 'test@test.test'
        );

        assertEquals(DateTimeImmutable::createFromFormat('YmdHis', '20250101000000'), $dto->getStartDateImmutable());
        assertEquals(DateTimeImmutable::createFromFormat('YmdHis', '20250102000000'), $dto->getEndDateImmutable());
    }
}
