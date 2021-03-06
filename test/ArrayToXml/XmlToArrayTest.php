<?php

declare(strict_types=1);

namespace AlexTartanTest\Array2Xml;

use AlexTartan\Array2Xml\Exception\ConversionException;
use AlexTartan\Array2Xml\XmlToArray;
use DOMDocument;
use PHPUnit\Framework\TestCase;

use function implode;

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

    public function testSimpleConversionFromDomDocument(): void
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->loadXML(
            '<note><to>Tove</to><from>Jani</from><heading>Reminder</heading><body>Body node</body></note>'
        );

        $output = (new XmlToArray())->buildArrayFromDomDocument($doc);

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

    public function testBuildFromStringWithMultipleNodes(): void
    {
        $output = (new XmlToArray())->buildArrayFromString(
            '<note><to>run1</to><to>run2</to></note>'
        );

        self::assertSame(
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

    public function testBuildFromStringThrowsExceptionOnInvalidXml(): void
    {
        $this->expectException(ConversionException::class);
        $this->expectExceptionMessage("Start tag expected, '<' not found");

        (new XmlToArray())->buildArrayFromString(
            'no_xml'
        );
    }

    public function testFromDomDocumentWithCdata(): void
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
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

        self::assertSame(
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

    public function testXmlWithNamespacesButNotEnabled(): void
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->loadXML(
            implode(
                '',
                [
                    '<table xmlns="https://www.w3schools.com/furniture">',
                    '<name>African Coffee Table</name>',
                    '<width>80</width>',
                    '<length>120</length>',
                    '</table>',
                ]
            )
        );

        $output = (new XmlToArray())->buildArrayFromDomDocument($doc);

        self::assertSame(
            [
                'table' => [
                    'name'   => 'African Coffee Table',
                    'width'  => '80',
                    'length' => '120',
                ],
            ],
            $output
        );
    }

    public function testXmlWithNamespacesEnabled(): void
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->loadXML(
            implode(
                '',
                [
                    '<table xmlns="https://www.w3schools.com/furniture">',
                    '<name>African Coffee Table</name>',
                    '<width>80</width>',
                    '<length>120</length>',
                    '</table>',
                ]
            )
        );

        $output = (new XmlToArray(['useNamespaces' => true]))->buildArrayFromDomDocument($doc);

        self::assertSame(
            [
                'table' => [
                    'name'        => 'African Coffee Table',
                    'width'       => '80',
                    'length'      => '120',
                    '@attributes' => [
                        'xmlns' => 'https://www.w3schools.com/furniture',
                    ],
                ],
            ],
            $output
        );
    }

    public function testXmlWithPrefixedNamespacesEnabled(): void
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->loadXML(
            implode(
                '',
                [
                    '<root xmlns:h="http://www.w3.org/TR/html4/" xmlns:f="https://www.w3schools.com/furniture">',
                    '<h:table>',
                    '<h:tr>',
                    '<h:td id="2">Apples</h:td>',
                    '<h:td>Bananas</h:td>',
                    '</h:tr>',
                    '</h:table>',
                    '<f:table>',
                    '<f:name>African Coffee Table</f:name>',
                    '<f:width>80</f:width>',
                    '<f:length>120</f:length>',
                    '</f:table>',
                    '</root>',
                ]
            )
        );

        $output = (new XmlToArray(['useNamespaces' => true]))->buildArrayFromDomDocument($doc);

        self::assertSame(
            [
                'root' => [
                    'h:table'     => [
                        'h:tr' => [
                            'h:td' => [
                                [
                                    '@value'      => 'Apples',
                                    '@attributes' => [
                                        'id' => '2',
                                    ],
                                ],
                                'Bananas',
                            ],
                        ],
                    ],
                    'f:table'     => [
                        'f:name'   => 'African Coffee Table',
                        'f:width'  => '80',
                        'f:length' => '120',
                    ],
                    '@attributes' => [
                        'xmlns:h' => 'http://www.w3.org/TR/html4/',
                        'xmlns:f' => 'https://www.w3schools.com/furniture',
                    ],
                ],
            ],
            $output
        );
    }

    public function testXmlWithNodesHavingAttributes(): void
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->loadXML(
            implode(
                '',
                [
                    '<table>',
                    '<name id="1" attrib="test">African Coffee Table</name>',
                    '<width id="2">80</width>',
                    '<length id="3">120</length>',
                    '</table>',
                ]
            )
        );

        $output = (new XmlToArray(['useNamespaces' => true]))->buildArrayFromDomDocument($doc);

        self::assertSame(
            [
                'table' => [
                    'name'   => [
                        '@value'      => 'African Coffee Table',
                        '@attributes' => [
                            'id'     => '1',
                            'attrib' => 'test',
                        ],
                    ],
                    'width'  => [
                        '@value'      => '80',
                        '@attributes' => [
                            'id' => '2',
                        ],
                    ],
                    'length' => [
                        '@value'      => '120',
                        '@attributes' => [
                            'id' => '3',
                        ],
                    ],
                ],
            ],
            $output
        );
    }

    public function testXmlWithEmptyNodes(): void
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->loadXML(
            implode(
                '',
                [
                    '<table>',
                    '<name></name>',
                    '<width>80</width>',
                    '<length>120</length>',
                    '</table>',
                ]
            )
        );

        $output = (new XmlToArray())->buildArrayFromDomDocument($doc);

        self::assertSame(
            [
                'table' => [
                    'name'   => '',
                    'width'  => '80',
                    'length' => '120',
                ],
            ],
            $output
        );
    }

    public function testXmlWithForceOneElementNodes(): void
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->loadXML(
            implode(
                '',
                [
                    '<table>',
                    '<name>sd</name>',
                    '<width>80</width>',
                    '<length>120</length>',
                    '</table>',
                ]
            )
        );

        $output = (new XmlToArray(['forceOneElementArray' => true]))->buildArrayFromDomDocument($doc);

        self::assertSame(
            [
                'table' => [
                    'name'   => ['sd'],
                    'width'  => ['80'],
                    'length' => ['120'],
                ],
            ],
            $output
        );
    }
}
