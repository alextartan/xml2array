<?php
declare(strict_types=1);

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

namespace AlexTartan\Array2Xml;

use AlexTartan\Array2Xml\Exception\ConversionException;
use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * This class converts an array into an XML file
 */
final class ArrayToXml
{
    /** @var DOMDocument */
    private $xml;

    /** @var ArrayToXmlConfig */
    private $config;

    public function __construct(array $config = [])
    {
        // string $version = '1.0', string $encoding = 'UTF-8', bool $formatOutput = false
        $this->config = ArrayToXmlConfig::fromArray($config);

        $this->xml               = new DomDocument($this->config->getVersion(), $this->config->getEncoding());
        $this->xml->formatOutput = $this->config->isFormatOutput();
    }

    public function buildXml(array $data): DOMDocument
    {
        if (count($data) !== 1) {
            throw new ConversionException('Xml needs to have one root element');
        }

        $firstKey = array_keys($data)[0];

        $this->xml->appendChild($this->convert($firstKey, $data[$firstKey]));

        return $this->xml;
    }

    /**
     * Get string representation of boolean value.
     *
     * @param bool|string|int $value
     */
    private function bool2str($value): string
    {
        //convert boolean to text value.
        if ($value === true) {
            return 'true';
        }
        if ($value === false) {
            return 'false';
        }

        return (string)$value;
    }

    /**
     * Convert an Array to XML.
     *
     * @param string       $nodeName - name of the root node to be converted
     * @param array|string $data     - array to be converted
     */
    private function convert(string $nodeName, $data = []): DOMNode
    {
        $node = $this->xml->createElement($nodeName);

        if (is_array($data)) {
            $this->convertArray($node, $nodeName, $data);
        } else {
            $this->convertString($node, $this->bool2str($data));
        }

        return $node;
    }

    private function convertString(DOMElement $node, string $string): void
    {
        $node->appendChild($this->xml->createTextNode($string));
    }

    private function convertArray(DOMElement $node, string $nodeName, array $array): void
    {
        $array = $this->parseAttributes($node, $nodeName, $array);
        $array = $this->parseValue($node, $array);
        $array = $this->parseCdata($node, $array);

        // now parse the actual keys->value pairs
        foreach ($array as $key => $value) {
            if (!$this->isValidTagName($key)) {
                throw new ConversionException(
                    'Illegal character in tag name. tag: ' . $key . ' in node: ' . $nodeName
                );
            }
            if (is_array($value) && is_numeric(key($value))) {
                foreach ($value as $v) {
                    $node->appendChild($this->convert($key, $v));
                }
            } else {
                $node->appendChild($this->convert($key, $value));
            }
            unset($array[$key]); //remove the key from the array once done.
        }
    }

    private function parseAttributes(DOMElement $node, string $nodeName, array $array): array
    {
        $attributesKey = $this->config->getAttributesKey();

        if (array_key_exists($attributesKey, $array) && is_array($array[$attributesKey])) {
            foreach ($array[$attributesKey] as $key => $value) {
                if (!$this->isValidTagName($key)) {
                    throw new ConversionException(
                        'Illegal character in attribute name. attribute: ' . $key . ' in node: ' . $nodeName
                    );
                }
                $node->setAttribute($key, $this->bool2str($value));
            }
            unset($array[$attributesKey]); //remove the key from the array once done.
        }

        return $array;
    }

    private function parseValue(DOMElement $node, array $array): array
    {
        $valueKey = $this->config->getValueKey();

        if (array_key_exists($valueKey, $array)) {
            $node->appendChild($this->xml->createTextNode($this->bool2str($array[$valueKey])));
            //remove the key from the array once done.
            unset($array[$valueKey]);
        }

        return $array;
    }

    private function parseCdata(DOMElement $node, array $array): array
    {
        $cdataKey = $this->config->getCdataKey();

        if (array_key_exists($cdataKey, $array)) {
            $node->appendChild($this->xml->createCDATASection($this->bool2str($array[$cdataKey])));
            //remove the key from the array once done.
            unset($array[$cdataKey]);
        }

        return $array;
    }

    /**
     * Check if the tag name or attribute name contains illegal characters (http://www.w3.org/TR/xml/#sec-common-syn)
     */
    private function isValidTagName(string $tag): bool
    {
        $pattern = '/^[a-z_]+[a-z0-9\:\-\.\_]*[^:]*$/i';

        return (bool)preg_match($pattern, $tag, $matches) && $matches[0] === $tag;
    }
}
