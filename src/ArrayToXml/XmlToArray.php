<?php
declare(strict_types=1);

namespace RedLine\Array2Xml;

use DOMDocument;
use DOMNode;
use RedLine\Array2Xml\Exception\ConversionException;

/**
 * XML2Array: A class to convert XML to an array in PHP
 * Takes a DOMDocument object or an XML string as input.
 *
 * Author : Lalit Patel, Verdant Industries
 * Website: https://github.com/alextartan/xml2array
 * License: Apache License 2.0
 *          http://www.apache.org/licenses/LICENSE-2.0
 * Usage:
 *       $array = XmlToArray::createArrayFromString($xmlString);
 *       $array = XmlToArray::createArrayFromString($xmlString, ['useNamespaces' => false, ...]);
 *       $array = XmlToArray::createArrayFromDomDocument($domDoc);
 *       $array = XmlToArray::createArrayFromDomDocument($domDoc, ['useNamespaces' => false, ...]);
 */
final class XmlToArray
{
    /** The name of the XML attribute that indicates a namespace definition*/
    const ATTRIBUTE_NAMESPACE = 'xmlns';

    /** The string that separates the namespace attribute from the prefix for the namespace*/
    const ATTRIBUTE_NAMESPACE_SEPARATOR = ':';

    /** @var array The configuration of the current instance */
    private $config;

    /** @var DOMDocument The working XML document */
    private $xml;

    /** @var array The working list of XML namespaces */
    private $namespaces = [];

    /**
     * Constructor
     *
     * @param array $config The configuration to use for this instance
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge(
            [
                'version'       => '1.0',
                'encoding'      => 'UTF-8',
                'attributesKey' => '@attributes',
                'cdataKey'      => '@cdata',
                'valueKey'      => '@value',
                'useNamespaces' => false,
            ],
            $config
        );
    }


    /**
     * Creates a blank working XML document
     */
    private function createDomDocument(): DOMDocument
    {
        return new DOMDocument($this->config['version'], $this->config['encoding']);
    }

    /**
     * Convert an XML DOMDocument or XML string to an array
     *
     * @param string $inputXml The XML to convert to an array
     *
     * @return array An array representation of the input XML
     */
    public function buildArrayFromString(string $inputXml): array
    {
        $this->xml = $this->createDomDocument();
        $this->xmlLoader($this->xml, $inputXml);

        // Convert the XML to an array, starting with the root node
        $docNodeName         = $this->xml->documentElement->nodeName;
        $array[$docNodeName] = $this->convert($this->xml->documentElement);

        // Add namespace information to the root node
        if (!empty($this->namespaces)) {
            if (!isset($array[$docNodeName][$this->config['attributesKey']])) {
                $array[$docNodeName][$this->config['attributesKey']] = [];
            }
            foreach ($this->namespaces as $uri => $prefix) {
                if ($prefix) {
                    $prefix = self::ATTRIBUTE_NAMESPACE_SEPARATOR . $prefix;
                }
                $array[$docNodeName][$this->config['attributesKey']][self::ATTRIBUTE_NAMESPACE . $prefix] = $uri;
            }
        }

        return $array;
    }

    /**
     * Convert an XML DOMDocument or XML string to an array
     *
     * @param DOMDocument $inputXml The XML to convert to an array
     *
     * @return array An array representation of the input XML
     */
    public function buildArrayFromDomDocument(DOMDocument $inputXml): array
    {
        $this->xml = $inputXml;

        // Convert the XML to an array, starting with the root node
        $docNodeName         = $this->xml->documentElement->nodeName;
        $array[$docNodeName] = $this->convert($this->xml->documentElement);

        // Add namespace information to the root node
        if (!empty($this->namespaces)) {
            if (!isset($array[$docNodeName][$this->config['attributesKey']])) {
                $array[$docNodeName][$this->config['attributesKey']] = [];
            }
            foreach ($this->namespaces as $uri => $prefix) {
                if ($prefix) {
                    $prefix = self::ATTRIBUTE_NAMESPACE_SEPARATOR . $prefix;
                }
                $array[$docNodeName][$this->config['attributesKey']][self::ATTRIBUTE_NAMESPACE . $prefix] = $uri;
            }
        }

        return $array;
    }

    /**
     * Convert an XML DOMDocument (or part thereof) to an array
     *
     * @return array|string
     */
    private function convert(DOMNode $node)
    {
        $output = [];

        $this->collateNamespaces($node);
        switch ($node->nodeType) {
            case XML_CDATA_SECTION_NODE:
                $output[$this->config['cdataKey']] = trim($node->textContent);
                break;

            case XML_TEXT_NODE:
                $output = trim($node->textContent);
                break;

            case XML_ELEMENT_NODE:
                // for each child node, call the covert function recursively
                for ($i = 0, $m = $node->childNodes->length; $i < $m; $i++) {
                    $child = $node->childNodes->item($i);
                    $value = $this->convert($child);
                    /** @noinspection UnSafeIsSetOverArrayInspection */
                    if (isset($child->tagName)) {
                        $temp = $child->nodeName;

                        // assume more nodes of same kind are coming
                        if (!isset($output[$temp])) {
                            $output[$temp] = [];
                        }
                        $output[$temp][] = $value;
                    } elseif ($value !== '') {
                        //check if it is not an empty text node
                        $output = $value;
                    }
                }

                if (is_array($output)) {
                    // if only one node of its kind, assign it directly instead if array($value);
                    foreach ($output as $temp => $value) {
                        if (is_array($value) && count($value) === 1) {
                            $output[$temp] = $value[0];
                        }
                    }
                    if (empty($output)) {
                        //for empty nodes
                        $output = '';
                    }
                }

                // loop through the attributes and collect them
                if ($node->attributes->length) {
                    $attribute = [];
                    foreach ($node->attributes as $attributeName => $attributeNode) {
                        $attributeName             = $attributeNode->nodeName;
                        $attribute[$attributeName] = (string)$attributeNode->value;
                        $this->collateNamespaces($attributeNode);
                    }
                    // if its an leaf node, store the value in @value instead of directly storing it.
                    if (!is_array($output)) {
                        $output = [$this->config['valueKey'] => $output];
                    }
                    $output[$this->config['attributesKey']] = $attribute;
                }
                break;
        }

        return $output;
    }

    /**
     * Get the namespace of the supplied node, and add it to the list of known namespaces for this document
     *
     * @param DOMNode $node
     *
     * @return void
     */
    private function collateNamespaces(DOMNode $node)
    {
        if ($node->namespaceURI &&
            !array_key_exists($node->namespaceURI, $this->namespaces) &&
            $this->config['useNamespaces']
        ) {
            $this->namespaces[$node->namespaceURI] = $node->lookupPrefix($node->namespaceURI);
        }
    }

    /**
     * @return void
     */
    public function handleXmlError(int $errNo, string $errStr)
    {
        $constants = [];
        foreach (get_defined_constants() as $key => $value) {
            if ($value <= $errNo &&
                $value & $errNo &&
                strpos($key, 'E_') === 0
            ) {
                $constants[] = $key;
            }
        }

        throw new ConversionException(
            implode(' | ', $constants) . ' ' .
            trim(
                str_replace(
                    'DOMDocument::loadXML()',
                    '',
                    $errStr
                ),
                ' :'
            )
        );
    }

    private function xmlLoader(DOMDocument $xml, string $strXml): DOMDocument
    {
        set_error_handler([$this, 'handleXmlError']);
        $xml->loadXML($strXml);
        restore_error_handler();

        return $xml;
    }
}
