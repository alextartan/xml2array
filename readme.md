Readme
===

Array <-> XML conversion package

Based on Lalit.org's XML2Array and Array2XML classes.

Current build status
--------------------
[![Build Status](https://travis-ci.org/alextartan/xml2array.svg?branch=master)](https://travis-ci.org/alextartan/xml2array)

[![Coverage Status](https://coveralls.io/repos/github/alextartan/xml2array/badge.svg?branch=master)](https://coveralls.io/github/alextartan/xml2array?branch=master)

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/alextartan/xml2array/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/alextartan/xml2array/?branch=master)

Usage Examples
---
#### Basic usage

    $array = XML2Array::createArray($xml);
    $xml = Array2XML::createXml($array);

Note that there's no need to specify the 'rootNode' parameter from the previous implementation. If the array contains a single root item, that will automatically be used as the root node.

#### Preserve namespaces
    
    $config = array(
        'useNamespaces' => true,
    );
    $array = XML2Array::createArray($xml, $config);

#### Use JSON-friendly special keys
    
    $config = array(
        'attributesKey' => '$attributes',
        'cdataKey'      => '$cdata',
        'valueKey'      => '$value',
    );
    $array = XML2Array::createArray($xml, $config);
    $xml = Array2XML::createXml($array, $config);

Forked from https://github.com/rentpost/xml2array