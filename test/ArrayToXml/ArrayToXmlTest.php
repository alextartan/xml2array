<?php
declare(strict_types=1);

namespace RedLineTest\Array2Xml;

use PHPUnit\Framework\TestCase;
use RedLine\Array2Xml\ArrayToXml;
use RedLine\Array2Xml\XmlToArray;

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

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Xml needs to have one root element
     */
    public function testSimpleConversionFromStringFailsOnMultipleRootNodes()
    {
        (new ArrayToXml())->buildXml(
            [
                'note'           => [
                    'to'      => 'Tove',
                    'from'    => 'Jani',
                    'heading' => 'Reminder',
                    'body'    => 'Body node',
                ],
                'duplicate_root' => 'yes',
            ]
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

    public function testWithCData()
    {
        $doc           = new \DOMDocument('1.0', 'UTF-8');
        $doc->encoding = 'UTF-8';
        $doc->loadXML(
            implode(
                '',
                [
                    '<?xml version="1.0" encoding="UTF-8"?>',
                    '<messages>',
                    '<note id="501">',
                    '<to>Tove</to>',
                    '<from>Jani</from>',
                    '<heading>Reminder</heading>',
                    '<body><![CDATA[I can use double dashes as much as I want (along with <, &, \', and ")]]></body>',
                    '</note>',
                    '</messages>',
                ]
            )
        );

        $output = (new ArrayToXml)->buildXml(
            [
                'messages' => [
                    'note' => [
                        [
                            '@attributes' => [
                                'id' => '501',
                            ],
                            'to'          => 'Tove',
                            'from'        => 'Jani',
                            'heading'     => 'Reminder',
                            'body'        => [
                                '@cdata' => 'I can use double dashes as much as I want (along with <, &, \', and ")',
                            ],
                        ],
                    ],
                ],
            ]
        );

        static::assertSame(
            $doc->saveXML(),
            $output->saveXML()
        );
    }

    public function testWithValue()
    {
        $doc           = new \DOMDocument('1.0', 'UTF-8');
        $doc->encoding = 'UTF-8';
        $doc->loadXML(
            implode(
                '',
                [
                    '<?xml version="1.0" encoding="UTF-8"?>',
                    '<messages>',
                    '<note id="501">',
                    'test',
                    '<to>Tove</to>',
                    '<from>Jani</from>',
                    '<heading>Reminder</heading>',
                    '<body><![CDATA[I can use double dashes as much as I want (along with <, &, \', and ")]]></body>',
                    '</note>',
                    '</messages>',
                ]
            )
        );

        $output = (new ArrayToXml)->buildXml(
            [
                'messages' => [
                    'note' => [
                        [
                            '@attributes' => [
                                'id' => '501',
                            ],
                            '@value'      => 'test',
                            'to'          => 'Tove',
                            'from'        => 'Jani',
                            'heading'     => 'Reminder',
                            'body'        => [
                                '@cdata' => 'I can use double dashes as much as I want (along with <, &, \', and ")',
                            ],
                        ],
                    ],
                ],
            ]
        );

        static::assertSame(
            $doc->saveXML(),
            $output->saveXML()
        );
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Illegal character in tag name. tag: !WOW in node: note
     */
    public function testInvalidNodeName()
    {
        (new ArrayToXml)->buildXml(
            [
                'messages' => [
                    'note' => [
                        [
                            '!WOW' => 'Tove',
                        ],
                    ],
                ],
            ]
        );
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Illegal character in attribute name. attribute: !id in node: note
     */
    public function testInvalidNodeNameInAttributes()
    {
        $output = (new ArrayToXml)->buildXml(
            [
                'messages' => [
                    'note' => [
                        [

                            '@attributes' => [
                                '!id' => '501',
                            ],
                            'WOW'         => 'Tove',
                        ],
                    ],
                ],
            ]
        );
    }
}
