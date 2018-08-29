<?php
declare(strict_types=1);

namespace RedLineTest\Array2Xml;

use PHPUnit\Framework\TestCase;
use RedLine\Array2Xml\XmlToArray;

class XmlToArrayTest extends TestCase
{
    public function testBuildFromString()
    {
        $output = (new XmlToArray())->buildArrayFromString(
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

    public function testBuildFromStringWithMultipleNodes()
    {
        $output = (new XmlToArray())->buildArrayFromString(
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

    /**
     * @expectedException \RedLine\Array2Xml\Exception\ConversionException
     * @expectedExceptionMessage E_WARNING Start tag expected, '<' not found in Entity, line: 1
     */
    public function testBuildFromStringThrowsExceptionOnInvalidXml()
    {
        $output = (new XmlToArray())->buildArrayFromString(
            'no_xml'
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

    public function testFromDomDocumentWithCdata()
    {
        $doc = new \DOMDocument('1.0', 'UTF-8');
        $doc->loadXML(
            implode(
                '',
                [
                    '<note>',
                    '<to>Tove</to><from>Jani</from><heading>Reminder</heading>',
                    '<body><![CDATA[I can use double dashes as much as I want (along with <, &, \', and ")]]></body>',
                    '</note>',
                ]
            )
        );

        $output = (new XmlToArray())->buildArrayFromDomDocument($doc);

        static::assertSame(
            [
                'note' => [
                    'to'      => 'Tove',
                    'from'    => 'Jani',
                    'heading' => 'Reminder',
                    'body'    => [
                        '@cdata' => "I can use double dashes as much as I want (along with <, &, ', and \")",
                    ],
                ],
            ],
            $output
        );
    }
}
