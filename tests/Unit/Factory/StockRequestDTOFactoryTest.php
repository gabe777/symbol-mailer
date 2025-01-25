<?php

namespace App\Tests\Unit\Factory;

use App\DTO\StockRequestDTO;
use App\Enum\StockRequestParameters;
use App\Factory\StockRequestDTOFactory;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertInstanceOf;

class StockRequestDTOFactoryTest extends TestCase
{
    private Serializer $serializer;
    protected function setUp(): void
    {
        $this->serializer = new Serializer(
            [new DateTimeNormalizer(), new ObjectNormalizer()],
            [new JsonEncoder()]
        );
    }

    public function testCreateFromRequest_success_payload(): void
    {
        $content = json_encode([
            StockRequestParameters::START_DATE->value => '2017-01-01',
            StockRequestParameters::END_DATE->value => '2017-12-31',
            StockRequestParameters::EMAIL->value => 'test@example.com',
        ]);

        $request = $this->createMock(Request::class);
        $request->expects($this->exactly(2))->method('getContent')->willReturn($content);
        $request->expects($this->once())->method('get')->with(StockRequestParameters::COMPANY_SYMBOL->value)->willReturn('TSTR');

        $factory = new StockRequestDTOFactory($this->serializer);
        $result = $factory->createFromRequest($request);
        assertInstanceOf(StockRequestDTO::class, $result);

        $content = json_decode($content, true);
        $content[StockRequestParameters::COMPANY_SYMBOL->value] = 'TSTR';
        assertEquals($content, (array)$result);
    }

    public function testCreateFromRequest_success()
    {
        $expectedParams = function () {
            yield StockRequestParameters::COMPANY_SYMBOL->value;
            yield StockRequestParameters::START_DATE->value;
            yield StockRequestParameters::END_DATE->value;
            yield StockRequestParameters::EMAIL->value;
        };

        $request = $this->createMock(Request::class);
        $request->expects($this->exactly(4))->method('get')->with(
                $this->callback(function ($param) use ($expectedParams) {
                    static $generator;
                    if (!$generator) {
                        $generator = $expectedParams();
                    }

                    $this->assertEquals($generator->current(), $param);
                    $generator->next();

                    return true;
                })
            )->willReturn('testValue');

        $factory = new StockRequestDTOFactory($this->serializer);
        $result = $factory->createFromRequest($request);
        assertInstanceOf(StockRequestDTO::class, $result);
        assertEquals('testValue', $result->companySymbol);
    }

    public function testCreateFromRequest_missing_get()
    {
        $request = $this->createMock(Request::class);
        $request->method('get')->willReturn(null);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Missing required parameter\: \w+/');

        $factory = new StockRequestDTOFactory($this->serializer);
        $factory->createFromRequest($request);
    }

    public function testCreateFromRequest_missing_payload()
    {
        $request = $this->createMock(Request::class);
        $request->method('get')->willReturn('TSTR');
        $request->method('getContent')->willReturn('{"test": "testValue"}');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Missing required parameter.+/');

        $factory = new StockRequestDTOFactory($this->serializer);
        $factory->createFromRequest($request);
    }
}
