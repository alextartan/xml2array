<?php
declare(strict_types=1);

namespace Array2XmlTest;

use Array2Xml\XmlToArray;
use PHPUnit\Framework\TestCase;

class XmlToArrayTest extends TestCase
{
    public function testBuildFromString()
    {
        $x2a    = new XmlToArray();
        $output = $x2a->buildArrayFromString(
            '<note><to>Tove</to><from>Jani</from><heading>Reminder</heading><body>Body node</body></note>'
        );

        static::assertSame(
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

    public function testSimpleConversionFromDomDocument()
    {
        $doc = new \DOMDocument('1.0', 'UTF-8');
        $doc->loadXML(
            '<note><to>Tove</to><from>Jani</from><heading>Reminder</heading><body>Body node</body></note>'
        );

        $x2a    = new XmlToArray();
        $output = $x2a->buildArrayFromDomDocument($doc);

        static::assertSame(
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

    public function testBuildFromStringWithMultipleNodes()
    {
        $x2a    = new XmlToArray();
        $output = $x2a->buildArrayFromString(
            '<note><to>run1</to><to>run2</to></note>'
        );

        static::assertSame(
            [
                'note' => [
                    'to' => [
                        0 => 'run1',
                        1 => 'run2',
                    ],
                ],
            ],
            $output
        );
    }
}
