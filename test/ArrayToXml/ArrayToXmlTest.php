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

    public function testWithAttributes()
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
                    '<body>Don\'t forget me this weekend!</body>',
                    '</note>',
                    '<note id="502">',
                    '<to>Jani</to>',
                    '<from>Tove</from>',
                    '<heading>Re: Reminder</heading>',
                    '<body>I will not</body>',
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
                            'body'        => 'Don\'t forget me this weekend!',
                        ],
                        [
                            '@attributes' => [
                                'id' => '502',
                            ],
                            'to'          => 'Jani',
                            'from'        => 'Tove',
                            'heading'     => 'Re: Reminder',
                            'body'        => 'I will not',
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
}
