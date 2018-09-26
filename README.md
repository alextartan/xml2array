#### Array <-> XML conversion package

Lightweight XML parser

Current build status
===

[![Build Status](https://travis-ci.org/alextartan/xml2array.svg?branch=master)](https://travis-ci.org/alextartan/xml2array) [![Coverage Status](https://coveralls.io/repos/github/alextartan/xml2array/badge.svg?branch=master)](https://coveralls.io/github/alextartan/xml2array?branch=master) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/alextartan/xml2array/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/alextartan/xml2array/?branch=master)

Install
===

The easiest way is to use `composer`:

    composer require alextartan/xml2array

Note: requires `PHP` >= 7.0 and the `dom` extension (`ext-dom`)

Usage
===

###### ArrayToXml
Convert an XML (either DOMDocument or string) to an array

    // default value:
    $config =  [
        'version'       => '1.0',
        'encoding'      => 'UTF-8',
        'attributesKey' => '@attributes',
        'cdataKey'      => '@cdata',
        'valueKey'      => '@value',
        'useNamespaces' => false,
    ];

    $xtoa  = new XmlToArray($config);
    $array = $xtoa->buildArrayFromString($xmlString);
    $array = $xtoa->buildArrayFromDomDocument($xmlDom);

###### XmlToArray
Convert an array to a DOMDocument

    // default value:
    $config =  [
        'version'       => '1.0',
        'encoding'      => 'UTF-8',
        'attributesKey' => '@attributes',
        'cdataKey'      => '@cdata',
        'valueKey'      => '@value',
        'formatOutput'  => false,
    ];

    $atox = new ArrayToXml($config);
    $xml  = $atox->buildXml($array);


Issues and pull requests.
===

Any issues found should be reported in this repository issue tracker, issues will be fixed when possible.
Pull requests will be accepted, but please adhere to the PSR2 coding standard. All builds must pass in order to merge the PR.