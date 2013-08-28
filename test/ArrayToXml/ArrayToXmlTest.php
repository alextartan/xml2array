<?php
/**
 * Created by PhpStorm.
 * User: redline
 * Date: 28.08.2018
 * Time: 16:06
 */

namespace Array2XmlTest;

use Array2Xml\ArrayToXml;
use Array2Xml\XmlToArray;
use PHPUnit\Framework\TestCase;

class ArrayToXmlTest extends TestCase
{
    public function testSimpleConversionFromString()
    {
        $doc           = new \DOMDocument('1.0', 'UTF-8');
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

        static::assertSame(
            $doc->saveXML(),
            $output->saveXML()
        );
    }

    public function testSimpleConversionFromDomDocument()
    {
        $doc = new \DOMDocument('1.0', 'UTF-8');
        $doc->loadXML(
            '<?xml version="1.0" encoding="UTF-8"?>' .
            '<note><to>Tove</to><from>Jani</from><heading>Reminder</heading><body>Body node</body></note>'
        );

        $output = (new XmlToArray())->buildArrayFromDomDocument($doc);

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

    public function testConversionFromStringWithMultipleNodes()
    {
        $doc           = new \DOMDocument('1.0', 'UTF-8');
        $doc->encoding = 'UTF-8';
        $doc->loadXML(
            '<?xml version="1.0" encoding="UTF-8"?>' .
            '<note><to><name>n1</name><file>q</file></to><to><name>n2</name><file>f</file></to></note>'
        );

        $output = (new ArrayToXml)->buildXml(
            [
                'note' => [
                    'to' => [
                        ['name' => 'n1', 'file' => 'q'],
                        ['name' => 'n2', 'file' => 'f'],
                    ],
                ],
            ]
        );

        static::assertSame(
            $doc->saveXML(),
            $output->saveXML()
        );
    }
}
