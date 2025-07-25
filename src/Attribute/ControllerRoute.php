<?php

namespace PhpDevCommunity\Attribute;

use PhpDevCommunity\Helper;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class ControllerRoute
{
    private string $path;
    private ?string $format;

    public function __construct(string $path, string $format = null)
    {
        $this->path = Helper::trimPath($path);
        $this->format = $format;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getFormat(): ?string
    {
        return $this->format;
    }
}