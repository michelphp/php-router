<?php

namespace PhpDevCommunity\Attribute;

use PhpDevCommunity\Helper;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class Route
{
    private string $path;
    private string $name;
    /**
     * @var array|string[]
     */
    private array $methods;
    private array $options;
    private ?string $format;

    public function __construct(string $path, string $name, array $methods = ['GET', 'POST'], array $options = [], string $format = null)
    {
        $this->path = Helper::trimPath($path);
        $this->name = $name;
        $this->methods = $methods;
        $this->options = $options;
        $this->format = $format;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getMethods(): array
    {
        return $this->methods;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getFormat(): ?string
    {
        return $this->format;
    }
}