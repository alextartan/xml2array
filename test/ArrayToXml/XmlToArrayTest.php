<?php
declare(strict_types=1);

namespace AlexTartanTest\Array2Xml;

use AlexTartan\Array2Xml\XmlToArray;
use PHPUnit\Framework\TestCase;

final class XmlToArrayTest extends TestCase
{
    public function testBuildFromString(): void
    {
        $output = (new XmlToArray())->buildArrayFromString(
            '<note><to>Tove</to><from>Jani</from><heading>Reminder</heading><body>Body node</body></note>'
        );

        self::assertSame(
            [
                'note' => [
                    'to'      => 'Tove',
                    'from'    => 'Jani',
                    'heading' => 'Reminder',
                    'body'    => 'Body node',
                ],
            ],
            $output
        );
    }
}
