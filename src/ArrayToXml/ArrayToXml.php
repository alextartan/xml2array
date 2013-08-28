<?php
declare(strict_types=1);

namespace Array2Xml;

use DOMDocument;
use DOMNode;

/**
 * Array2XML: A class to convert array in PHP to XML
 * Returns the XML in form of DOMDocument class.
 * Throws an exception if the tag name or attribute name has illegal chars.
 * Takes into account attributes names unlike SimpleXML in PHP.
 *
 * Author : Lalit Patel, Verdant Industries
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
    /** @var string */
    private $encoding = 'UTF-8';

    /** @var DOMDocument */
    private $xml;

    /**
     * Convert an Array to XML.
     *
     * @param string $nodeName - name of the root node to be converted
     * @param array  $arr      - array to be converted
     *
     * @return DOMDocument
     */
    public function createXML(string $nodeName, array $arr = []): DOMDocument
    {
        $xml = $this->getXmlRoot();
        $xml->appendChild($this->convert($nodeName, $arr));

        return $xml;
    }

    public function buildXml(array $data): DOMDocument
    {
        if (count($data) !== 1) {
            throw new \InvalidArgumentException('Xml needs to have one root element');
        }

        $firstKey = array_keys($data)[0];

        return $this->createXML($firstKey, $data[$firstKey]);
    }

    /**
     * Initialize the root XML node [optional].
     *
     * @param string $version
     * @param string $encoding
     * @param bool   $formatOutput
     */
    public function init(string $version = '1.0', string $encoding = 'UTF-8', bool $formatOutput = false)
    {
        $this->xml               = new DomDocument($version, $encoding);
        $this->xml->formatOutput = $formatOutput;
        $this->encoding          = $encoding;
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
     * @param string $nodeName - name of the root node to be converted
     * @param array  $arr      - array to be converted
     *
     * @return DOMNode
     *
     * @throws \InvalidArgumentException
     */
    private function convert($nodeName, $arr = []): DOMNode
    {
        //print_arr($node_name);
        $xml  = $this->getXmlRoot();
        $node = $xml->createElement($nodeName);
        if (is_array($arr)) {
            if (array_key_exists('@attributes', $arr) && is_array($arr['@attributes'])) {
                foreach ($arr['@attributes'] as $key => $value) {
                    if (!$this->isValidTagName($key)) {
                        throw new \InvalidArgumentException(
                            '[Array2XML] Illegal character in attribute name. attribute: ' . $key . ' in node: ' . $nodeName
                        );
                    }
                    $node->setAttribute($key, $this->bool2str($value));
                }
                unset($arr['@attributes']); //remove the key from the array once done.
            }

            if (array_key_exists('@value', $arr)) {
                $node->appendChild($xml->createTextNode($this->bool2str($arr['@value'])));
                //remove the key from the array once done.
                unset($arr['@value']);

                //return from recursion, as a note with value cannot have child nodes.
                return $node;
            }

            if (array_key_exists('@cdata', $arr)) {
                $node->appendChild($xml->createCDATASection($this->bool2str($arr['@cdata'])));
                //remove the key from the array once done.
                unset($arr['@cdata']);

                //return from recursion, as a note with cdata cannot have child nodes.
                return $node;
            }
        }
        //create subnodes using recursion
        if (is_array($arr)) {
            // recurse to get the node for that key
            foreach ($arr as $key => $value) {
                if (!$this->isValidTagName($key)) {
                    throw new \Exception('[Array2XML] Illegal character in tag name. tag: ' . $key . ' in node: ' . $nodeName);
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
                unset($arr[$key]); //remove the key from the array once done.
            }
        }

        // after we are done with all the keys in the array (if it is one)
        // we check if it has any text value, if yes, append it.
        if (!is_array($arr)) {
            $node->appendChild($xml->createTextNode($this->bool2str($arr)));
        }

        return $node;
    }

    /**
     * Get the root XML node, if there isn't one, create it.
     *
     * @return DomDocument
     */
    private function getXmlRoot(): DOMDocument
    {
        if ($this->xml === null) {
            $this->init();
        }

        return $this->xml;
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
