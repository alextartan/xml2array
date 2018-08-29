<?php
declare(strict_types=1);

namespace RedLineTest\Array2Xml\Exception;

use PHPUnit\Framework\TestCase;
use RedLine\Array2Xml\Exception\ConversionException;

class ConversionExceptionTest extends TestCase
{
    public function testCorrectType()
    {
        $exception = new ConversionException('someText');

        static::assertInstanceOf(\Exception::class, $exception);
    }
}
