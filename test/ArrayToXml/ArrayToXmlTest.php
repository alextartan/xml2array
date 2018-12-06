<?php
declare(strict_types=1);

namespace AlexTartanTest\Array2Xml;

use AlexTartan\Array2Xml\ArrayToXml;
use AlexTartan\Array2Xml\Exception\ConversionException;
use AlexTartan\Array2Xml\XmlToArray;
use PHPUnit\Framework\TestCase;

final class ArrayToXmlTest extends TestCase
{
    public function testSimpleConversionFromString(): void
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

    public function testSimpleConversionFromStringFailsOnMultipleRootNodes(): void
    {
        $this->expectException(ConversionException::class);
        $this->expectExceptionMessage('Xml needs to have one root element');

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

    public function testSimpleConversionFromDomDocument(): void
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

    public function testConversionFromStringWithMultipleNodes(): void
    {
        $doc           = new \DOMDocument('1.0', 'UTF-8');
        $doc->encoding = 'UTF-8';
        $doc->loadXML(
            '<?xml version="1.0" encoding="UTF-8"?>' .
            '<note><to><name>n1</name><file>q</file></to><to><name>n2</name><file>f</file></to></note>'
        );

        $output = (new ArrayToXml())->buildXml(
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

    public function testWithCData(): void
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

        $output = (new ArrayToXml())->buildXml(
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

    public function testWithValue(): void
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

        $output = (new ArrayToXml())->buildXml(
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

    public function testInvalidNodeName(): void
    {
        $this->expectException(ConversionException::class);
        $this->expectExceptionMessage('Illegal character in tag name. tag: !WOW in node: note');

        (new ArrayToXml())->buildXml(
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

    public function testInvalidNodeNameInAttributes(): void
    {
        $this->expectException(ConversionException::class);
        $this->expectExceptionMessage('Illegal character in attribute name. attribute: !id in node: note');

        (new ArrayToXml())->buildXml(
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

    public function testBool2Str(): void
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
                    '<from>true</from>',
                    '<heading>false</heading>',
                    '</note>',
                    '</messages>',
                ]
            )
        );

        $output = (new ArrayToXml())->buildXml(
            [
                'messages' => [
                    'note' => [
                        [
                            '@attributes' => [
                                'id' => '501',
                            ],
                            '@value'      => 'test',
                            'to'          => 'Tove',
                            'from'        => true,
                            'heading'     => false,
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
