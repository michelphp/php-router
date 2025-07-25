<?php

declare(strict_types=1);

namespace PhpDevCommunity;

use ArrayAccess;
use InvalidArgumentException;
use function array_key_exists;
use function implode;
use function sprintf;
use function str_replace;
use function trim;

final class UrlGenerator
{
    private ArrayAccess $routes;
    private string $defaultUri;

    /**
     * Constructor for the UrlGenerator class.
     *
     * @param ArrayAccess $routes The routes object.
     * @param string $defaultUri The default URI.
     */
    public function __construct(ArrayAccess $routes, string $defaultUri = '')
    {
        $this->routes = $routes;
        $this->defaultUri = $defaultUri;
    }

    /**
     * Generates a URL based on the given route name and parameters.
     *
     * @param string $name The name of the route.
     * @param array $parameters The parameters for the route. Default is an empty array.
     * @param bool $absoluteUrl Whether to generate an absolute URL. Default is false.
     * @return string The generated URL.
     * @throws InvalidArgumentException If the route name is unknown or if the route requires parameters but none are provided.
     */
    public function generate(string $name, array $parameters = [], bool $absoluteUrl = false): string
    {
        if ($this->routes->offsetExists($name) === false) {
            throw new InvalidArgumentException(
                sprintf('Unknown %s name route', $name)
            );
        }
        /*** @var Route $route */
        $route = $this->routes[$name];
        if ($route->hasAttributes() === true && $parameters === []) {
            throw new InvalidArgumentException(
                sprintf('%s route need parameters: %s', $name, implode(',', $route->getVarsNames()))
            );
        }

        $url = self::resolveUri($route, $parameters);
        if ($absoluteUrl === true) {
            $url = ltrim(Helper::trimPath($this->defaultUri), '/') . $url;
        }
        return $url;
    }

    private static function resolveUri(Route $route, array $parameters): string
    {
        $uri = $route->getPath();
        foreach ($route->getVarsNames() as $variable) {
            $varName = trim($variable, '{\}');
            if (array_key_exists($varName, $parameters) === false) {
                throw new InvalidArgumentException(
                    sprintf('%s not found in parameters to generate url', $varName)
                );
            }
            if (!is_array($parameters[$varName])) {
                $parameters[$varName] = strval($parameters[$varName]);
            }
            $uri = str_replace($variable, $parameters[$varName], $uri);
        }
        return $uri;
    }
}
