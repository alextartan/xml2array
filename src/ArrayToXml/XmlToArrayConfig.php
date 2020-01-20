<?php

declare(strict_types=1);

namespace AlexTartan\Array2Xml;

use function array_merge;

final class XmlToArrayConfig
{
    private const DEFAULTS = [
        'version'              => '1.0',
        'encoding'             => 'UTF-8',
        'attributesKey'        => '@attributes',
        'cdataKey'             => '@cdata',
        'valueKey'             => '@value',
        'useNamespaces'        => false,
        'forceOneElementArray' => false,
    ];

    private string $version;

    private string $encoding;

    private string $attributesKey;

    private string $cdataKey;

    private string $valueKey;

    private bool $useNamespaces;

    private bool $forceOneElementArray;

    private function __construct(
        string $version,
        string $encoding,
        string $attributesKey,
        string $cdataKey,
        string $valueKey,
        bool $useNamespaces,
        bool $forceOneElementArray
    ) {
        $this->version              = $version;
        $this->encoding             = $encoding;
        $this->attributesKey        = $attributesKey;
        $this->cdataKey             = $cdataKey;
        $this->valueKey             = $valueKey;
        $this->useNamespaces        = $useNamespaces;
        $this->forceOneElementArray = $forceOneElementArray;
    }

    public static function fromArray(array $configData = []): self
    {
        $config = array_merge(self::DEFAULTS, $configData);

        return new self(
            $config['version'],
            $config['encoding'],
            $config['attributesKey'],
            $config['cdataKey'],
            $config['valueKey'],
            (bool)$config['useNamespaces'],
            (bool)$config['forceOneElementArray']
        );
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getEncoding(): string
    {
        return $this->encoding;
    }

    public function getAttributesKey(): string
    {
        return $this->attributesKey;
    }

    public function getCdataKey(): string
    {
        return $this->cdataKey;
    }

    public function getValueKey(): string
    {
        return $this->valueKey;
    }

    public function isUseNamespaces(): bool
    {
        return $this->useNamespaces;
    }

    public function isForceOneElementArray(): bool
    {
        return $this->forceOneElementArray;
    }
}
