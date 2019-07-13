<?php
declare(strict_types=1);

namespace AlexTartanTest\Array2Xml;

use AlexTartan\Array2Xml\ArrayToXml;
use DOMDocument;
use PHPUnit\Framework\TestCase;

final class ArrayToXmlTest extends TestCase
{
    public function testSimpleConversionFromString(): void
    {
        $doc           = new DOMDocument('1.0', 'UTF-8');
        $doc->encoding = 'UTF-8';
        $doc->loadXML(
            '<?xml version="1.0" encoding="UTF-8"?>' .
            '<note><to>Tove</to><from>Jani</from><heading>Reminder</heading><body>Body node</body></note>'
        );

        $output = (new ArrayToXml())->buildXml(
            [
                'note' => [
                    'to'      => 'Tove',
                    'from'    => 'Jani',
                    'heading' => 'Reminder',
                    'body'    => 'Body node',
                ],
            ]
        );

        self::assertSame(
            $doc->saveXML(),
            $output->saveXML()
        );
    }
}
