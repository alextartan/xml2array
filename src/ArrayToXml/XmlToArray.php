<?php

/**
 * Copyright 2018-present AlexTartan. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 **/

declare(strict_types=1);

namespace AlexTartan\Array2Xml;

use AlexTartan\Array2Xml\Exception\ConversionException;
use DOMDocument;
use DOMElement;
use DOMNode;
use LibXMLError;

use function array_key_exists;
use function is_array;
use function is_string;
use function libxml_clear_errors;
use function libxml_get_last_error;
use function libxml_use_internal_errors;
use function trim;

use const XML_CDATA_SECTION_NODE;
use const XML_ELEMENT_NODE;
use const XML_TEXT_NODE;

/**
 * This class helps convert an XML to an array
 */
final class XmlToArray
{
    /** The name of the XML attribute that indicates a namespace definition*/
    private const ATTRIBUTE_NAMESPACE = 'xmlns';

    /** The string that separates the namespace attribute from the prefix for the namespace*/
    private const ATTRIBUTE_NAMESPACE_SEPARATOR = ':';

    private XmlToArrayConfig $config;

    private DOMDocument $xml;

    /** @var string[] The working list of XML namespaces */
    private array $namespaces = [];

    public function __construct(array $config = [])
    {
        $this->config = XmlToArrayConfig::fromArray($config);
    }

    /**
     * Convert an XML string to an array
     *
     * @param string $inputXml The XML to convert to an array
     *
     * @return array An array representation of the input XML
     *
     * @throws ConversionException
     */
    public function buildArrayFromString(string $inputXml): array
    {
        $this->xml = new DOMDocument($this->config->getVersion(), $this->config->getEncoding());
        $this->xmlLoader($this->xml, $inputXml);

        return $this->extractArray();
    }

    /**
     * Convert an XML DOMDocument to an array
     *
     * @param DOMDocument $inputXml The XML to convert to an array
     *
     * @return array An array representation of the input XML
     */
    public function buildArrayFromDomDocument(DOMDocument $inputXml): array
    {
        $this->xml = $inputXml;

        return $this->extractArray();
    }

    private function extractArray(): array
    {
        // Convert the XML to an array, starting with the root node
        $docNodeName = $this->xml->documentElement->nodeName;
        $array       = [];

        $convertedElement = $this->convert($this->xml->documentElement);

        // force $array[$docNodeName] to be an array
        $array[$docNodeName] = is_array($convertedElement) ? $convertedElement : [$convertedElement];

        $attributesKey = $this->config->getAttributesKey();

        if (count($this->namespaces) === 0) {
            // no namespaces, just return
            return $array;
        }

        // Add namespace information to the root node
        foreach ($this->namespaces as $uri => $prefix) {
            if ($prefix !== '') {
                $prefix = self::ATTRIBUTE_NAMESPACE_SEPARATOR . $prefix;
            }
            $array[$docNodeName][$attributesKey][self::ATTRIBUTE_NAMESPACE . $prefix] = $uri;
        }

        return $array;
    }

    /**
     * Convert an XML DOMDocument (or part thereof) to an array
     *
     * @return string|string[]|string[][]
     */
    private function convert(DOMNode $node)
    {
        $output = [];

        $this->collateNamespaces($node);
        switch ($node->nodeType) {
            case XML_CDATA_SECTION_NODE:
                $output[$this->config->getCdataKey()] = trim($node->textContent);
                break;

            case XML_TEXT_NODE:
                $output = trim($node->textContent);
                break;

            case XML_ELEMENT_NODE:
                $output = $this->loopNodeChildren($node, $output);

                $output = $this->normalizeValues($output);

                $output = $this->collectAttributes($node, $output);

                break;
        }

        return $output;
    }

    /**
     * For each child node, call the covert function recursively
     *
     * @param DOMNode             $node
     * @param string[]|string[][] $output
     *
     * @return string|string[]|string[][]
     */
    private function loopNodeChildren(DOMNode $node, array $output)
    {
        foreach ($node->childNodes as $child) {
            /** @var DOMNode $child */
            $value = $this->convert($child);

            if ($child instanceof DOMElement) {
                $temp = $child->nodeName;

                // assume more nodes of same kind are coming
                if (!isset($output[$temp])) {
                    $output[$temp] = [];
                }
                /** @noinspection UnsupportedStringOffsetOperationsInspection */
                $output[$temp][] = $value;
            } elseif (is_string($value) && $value !== '') {
                //check if it is not an empty text node
                return $value;
            } elseif (is_array($value) && count($value) !== 0) {
                //check if it is not an empty array node
                return $value;
            }
        }

        return $output;
    }

    /**
     * Normalize 1-item array values and empty nodes
     *
     * @param string|string[]|string[][] $output
     *
     * @return string|string[]|string[][]
     */
    private function normalizeValues($output)
    {
        if (!is_array($output)) {
            return $output;
        }

        if (!$this->config->isForceOneElementArray()) {
            // if only one node of its kind, assign it directly instead if array($value);
            foreach ($output as $key => $value) {
                if (is_array($value) && count($value) === 1) {
                    $output[$key] = $value[0];
                }
            }
        }

        if (count($output) === 0) {
            //for empty nodes
            $output = '';
        }

        return $output;
    }

    /**
     * Loop through the attributes and collect them
     *
     * @param string|string[]|string[][] $output
     *
     * @return string|string[]|string[][]
     */
    private function collectAttributes(DOMNode $node, $output)
    {
        if ($node->attributes->length === 0) {
            return $output;
        }

        $attribute = [];
        foreach ($node->attributes as $attributeName => $attributeNode) {
            $attributeName             = $attributeNode->nodeName;
            $attribute[$attributeName] = (string)$attributeNode->value;
            $this->collateNamespaces($attributeNode);
        }

        // if its a leaf node, store the value in @value instead of directly it.
        if (!is_array($output)) {
            $output = [$this->config->getValueKey() => $output];
        }
        $output[$this->config->getAttributesKey()] = $attribute;

        return $output;
    }

    /**
     * Get the namespace of the supplied node, and add it to the list of known namespaces for this document
     */
    private function collateNamespaces(DOMNode $node): void
    {
        if (
            $node->namespaceURI !== '' &&
            $node->namespaceURI !== null &&
            !array_key_exists($node->namespaceURI, $this->namespaces) &&
            $this->config->isUseNamespaces()
        ) {
            $this->namespaces[$node->namespaceURI] = $node->lookupPrefix($node->namespaceURI);
        }
    }

    private function xmlLoader(DOMDocument $xml, string $strXml): DOMDocument
    {
        libxml_use_internal_errors(true);
        $xml->loadXML($strXml);
        $firstError = libxml_get_last_error();
        libxml_clear_errors();

        if ($firstError instanceof LibXMLError) {
            throw new ConversionException($firstError->message);
        }

        return $xml;
    }
}
