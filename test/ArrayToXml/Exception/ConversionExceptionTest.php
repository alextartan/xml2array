<?php
declare(strict_types=1);

namespace AlexTartanTest\Array2Xml\Exception;

use AlexTartan\Array2Xml\Exception\ConversionException;
use PHPUnit\Framework\TestCase;

final class ConversionExceptionTest extends TestCase
{
    public function testCorrectType(): void
    {
        $msg       = 'someText';
        $exception = new ConversionException($msg);

        self::assertSame($msg, $exception->getMessage());
    }
}
