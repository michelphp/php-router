<?php

declare(strict_types=1);

namespace PhpDevCommunity;

use PhpDevCommunity\Traits\RouteTrait;
use InvalidArgumentException;
use function array_filter;
use function is_string;
use function preg_match;
use function preg_match_all;
use function reset;
use function str_replace;
use function trim;

final class Route
{
    use RouteTrait;

    private string $name;
    private string $path;

    /**
     * @var mixed
     */
    private $handler;

    /**
     * @var array<string>
     */
    private array $methods = [];

    /**
     * @var array<string>
     */
    private array $attributes = [];

    /**
     * @var array<string, string>
     */
    private array $wheres = [];

    /**
     * Constructor for the Route class.
     *
     * @param string $name The name of the route.
     * @param string $path The path of the route.
     * @param mixed $handler The handler for the route.
     *    $handler = [
     *      0 => (string) Controller name : HomeController::class.
     *      1 => (string|null) Method name or null if invoke method
     *    ]
     * @param array $methods The HTTP methods for the route. Default is ['GET', 'HEAD'].
     *
     * @throws InvalidArgumentException If the HTTP methods argument is empty.
     */
    public function __construct(string $name, string $path, $handler, array $methods = ['GET', 'HEAD'])
    {
        if ($methods === []) {
            throw new InvalidArgumentException('HTTP methods argument was empty; must contain at least one method');
        }
        $this->name = $name;
        $this->path = Helper::trimPath($path);
        $this->handler = $handler;
        $this->methods = $methods;

        if (in_array('GET', $this->methods) && !in_array('HEAD', $this->methods)) {
            $this->methods[] = 'HEAD';
        }
    }

    /**
     * Matches a given path against the route's path and extracts attribute values.
     *
     * @param string $path The path to match against.
     * @return bool True if the path matches the route's path, false otherwise.
     */
    public function match(string $path): bool
    {
        $regex = $this->getPath();
        // This loop replaces all route variables like {var} or {var*} with corresponding regex patterns.
        // If the variable name ends with '*', it means the value can contain slashes (e.g. /foo/bar).
        // In that case, we use a permissive regex: (?P<varName>.+) — matches everything including slashes.
        // Otherwise, we use a strict regex: (?P<varName>[^/]++), which excludes slashes for standard segments.
        // The possessive quantifier '++' is used for better performance (avoids unnecessary backtracking).
        foreach ($this->getVarsNames() as $variable) {
            $varName = trim($variable, '{\}');
            $end = '*';
            if ((@substr_compare($varName, $end, -strlen($end)) == 0)) {
                $varName = rtrim($varName, $end);
                $regex = str_replace($variable, '(?P<' . $varName . '>.+)', $regex); // allows slashes
                continue;
            }
            $regex = str_replace($variable, '(?P<' . $varName . '>[^/]++)', $regex); // faster, excludes slashes
        }

        if (!preg_match('#^' . $regex . '$#sD', Helper::trimPath($path), $matches)) {
            return false;
        }

        $values = array_filter($matches, static function ($key) {
            return is_string($key);
        }, ARRAY_FILTER_USE_KEY);

        foreach ($values as $key => $value) {
            if (array_key_exists($key, $this->wheres)) {
                $pattern = $this->wheres[$key];
                $delimiter = '#';
                $regex = $delimiter . '^' . $pattern . '$' . $delimiter;
                if (!preg_match($regex, $value)) {
                    return false;
                }
            }
            $this->attributes[$key] = $value;
        }

        return true;
    }

    /**
     * Returns the name of the Route.
     *
     * @return string The name of the Route.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns the path of the Route.
     *
     * @return string The path of the Route.
     */
    public function getPath(): string
    {
        return $this->path;
    }

    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * Returns the HTTP methods for the Route.
     *
     * @return array The HTTP methods for the Route.
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    public function getVarsNames(): array
    {
        preg_match_all('/{[^}]*}/', $this->path, $matches);
        return reset($matches) ?? [];
    }

    public function hasAttributes(): bool
    {
        return $this->getVarsNames() !== [];
    }

    /**
     * @return array<string>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Sets a number constraint on the specified route parameters.
     *
     * @param mixed ...$parameters The route parameters to apply the constraint to.
     * @return self The updated Route instance.
     */
    public function whereNumber(...$parameters): self
    {
        $this->assignExprToParameters($parameters, '[0-9]+');
        return $this;
    }

    /**
     * Sets a slug constraint on the specified route parameters.
     *
     * @param mixed ...$parameters The route parameters to apply the constraint to.
     * @return self The updated Route instance.
     */
    public function whereSlug(...$parameters): self
    {
        $this->assignExprToParameters($parameters, '[a-z0-9-]+');
        return $this;
    }

    /**
     * Sets an alphanumeric constraint on the specified route parameters.
     *
     * @param mixed ...$parameters The route parameters to apply the constraint to.
     * @return self The updated Route instance.
     */
    public function whereAlphaNumeric(...$parameters): self
    {
        $this->assignExprToParameters($parameters, '[a-zA-Z0-9]+');
        return $this;
    }

    /**
     * Sets an alphabetic constraint on the specified route parameters.
     *
     * @param mixed ...$parameters The route parameters to apply the constraint to.
     * @return self The updated Route instance.
     */
    public function whereAlpha(...$parameters): self
    {
        $this->assignExprToParameters($parameters, '[a-zA-Z]+');
        return $this;
    }

    public function whereTwoSegments(...$parameters): self
    {
        $this->assignExprToParameters($parameters, '[a-zA-Z0-9\-_]+/[a-zA-Z0-9\-_]+');
        foreach ($parameters as $parameter) {
            $this->path = str_replace(sprintf('{%s}', $parameter), sprintf('{%s*}', $parameter), $this->path);
        }
        return $this;
    }

    public function whereAnything(string $parameter): self
    {
        $this->assignExprToParameters([$parameter], '.+');
        $this->path = str_replace(sprintf('{%s}', $parameter), sprintf('{%s*}', $parameter), $this->path);
        return $this;
    }

    public function whereDate(...$parameters): self
    {
        $this->assignExprToParameters($parameters, '\d{4}-\d{2}-\d{2}');
        return $this;
    }

    public function whereYearMonth(...$parameters): self
    {
        $this->assignExprToParameters($parameters, '\d{4}-\d{2}');
        return $this;
    }

    public function whereEmail(...$parameters): self
    {
        $this->assignExprToParameters($parameters, '[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}');
        return $this;
    }

    public function whereUuid(...$parameters): self
    {
        $this->assignExprToParameters($parameters, '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[1-5][0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}');
        return $this;
    }

    public function whereBool(...$parameters): self
    {
        $this->assignExprToParameters($parameters, 'true|false|1|0');
        return $this;
    }

    /**
     * Sets a custom constraint on the specified route parameter.
     *
     * @param string $parameter The route parameter to apply the constraint to.
     * @param string $expression The regular expression constraint.
     * @return self The updated Route instance.
     */
    public function where(string $parameter, string $expression): self
    {
        $this->wheres[$parameter] = $expression;
        return $this;
    }

    private function assignExprToParameters(array $parameters, string $expression): void
    {
        foreach ($parameters as $parameter) {
            $this->where($parameter, $expression);
        }
    }
}
