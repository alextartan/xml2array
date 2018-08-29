<?php
declare(strict_types=1);

namespace RedLine\Array2Xml;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Array2XML: A class to convert array in PHP to XML
 * Returns the XML in form of DOMDocument class.
 *
 * Website: https://github.com/alextartan/xml2array
 * License: Apache License 2.0
 *          http://www.apache.org/licenses/LICENSE-2.0
 *
 * Usage:
 *       $xml = Array2XML::createXML($array);
 *       echo $xml->saveXML();
 */
final class ArrayToXml
{
    /** @var DOMDocument */
    private $xml;

    /**
     * Initialize the root XML node [optional].
     *
     * @param string $version
     * @param string $encoding
     * @param bool   $formatOutput
     */
    public function __construct(string $version = '1.0', string $encoding = 'UTF-8', bool $formatOutput = false)
    {
        $this->xml               = new DomDocument($version, $encoding);
        $this->xml->formatOutput = $formatOutput;
    }

    public function buildXml(array $data): DOMDocument
    {
        if (count($data) !== 1) {
            throw new \InvalidArgumentException('Xml needs to have one root element');
        }

        $firstKey = array_keys($data)[0];

        $this->xml->appendChild($this->convert($firstKey, $data[$firstKey]));

        return $this->xml;
    }

    /**
     * Get string representation of boolean value.
     *
     * @param mixed $value
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
     *
     * @return DOMNode
     *
     * @throws \InvalidArgumentException
     */
    private function convert(string $nodeName, $data = []): DOMNode
    {
        $node = $this->xml->createElement($nodeName);

        if (is_array($data)) {
            $this->convertArray($node, $nodeName, $data);
        } else {
            // after we are done with all the keys in the array (if it is one)
            // we check if it has any text value, if yes, append it.
            $this->convertString($node, $data);
        }

        return $node;
    }

    private function convertString(DOMElement $node, string $string)
    {
        $node->appendChild($this->xml->createTextNode($this->bool2str($string)));
    }

    private function convertArray(DOMElement $node, string $nodeName, array $array)
    {
        $array = $this->parseAttributes($node, $nodeName, $array);
        $array = $this->parseValue($node, $array);
        $array = $this->parseCdata($node, $array);

        // now parse the actual keys->value pairs
        foreach ($array as $key => $value) {
            if (!$this->isValidTagName($key)) {
                throw new \Exception(
                    'Illegal character in tag name. tag: ' . $key . ' in node: ' . $nodeName
                );
            }
            if (is_array($value) && is_numeric(key($value))) {
                // MORE THAN ONE NODE OF ITS KIND;
                // if the new array is numeric index, means it is array of nodes of the same kind
                // it should follow the parent key name
                foreach ($value as $v) {
                    $node->appendChild($this->convert($key, $v));
                }
            } else {
                // ONLY ONE NODE OF ITS KIND
                $node->appendChild($this->convert($key, $value));
            }
            unset($array[$key]); //remove the key from the array once done.
        }
    }

    private function parseAttributes(DOMElement $node, string $nodeName, array $array): array
    {
        if (array_key_exists('@attributes', $array) && is_array($array['@attributes'])) {
            foreach ($array['@attributes'] as $key => $value) {
                if (!$this->isValidTagName($key)) {
                    throw new \InvalidArgumentException(
                        'Illegal character in attribute name. attribute: ' . $key . ' in node: ' . $nodeName
                    );
                }
                $node->setAttribute($key, $this->bool2str($value));
            }
            unset($array['@attributes']); //remove the key from the array once done.
        }

        return $array;
    }

    private function parseValue(DOMElement $node, array $array): array
    {
        if (array_key_exists('@value', $array)) {
            $node->appendChild($this->xml->createTextNode($this->bool2str($array['@value'])));
            //remove the key from the array once done.
            unset($array['@value']);
        }

        return $array;
    }

    private function parseCdata(DOMElement $node, array $array): array
    {
        if (array_key_exists('@cdata', $array)) {
            $node->appendChild($this->xml->createCDATASection($this->bool2str($array['@cdata'])));
            //remove the key from the array once done.
            unset($array['@cdata']);
        }

        return $array;
    }

    /**
     * Check if the tag name or attribute name contains illegal characters
     * Ref: http://www.w3.org/TR/xml/#sec-common-syn.
     */
    private function isValidTagName(string $tag): bool
    {
        $pattern = '/^[a-z_]+[a-z0-9\:\-\.\_]*[^:]*$/i';

        return preg_match($pattern, $tag, $matches) && $matches[0] === $tag;
    }
}
