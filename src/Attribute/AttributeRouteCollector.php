<?php

namespace PhpDevCommunity\Attribute;

use PhpDevCommunity\Helper;

final class AttributeRouteCollector
{
    private array $classes;
    private ?string $cacheDir;

    public function __construct(array $classes, ?string $cacheDir = null)
    {
        if (PHP_VERSION_ID < 80000) {
            throw new \LogicException('Attribute routes are only supported in PHP 8.0+');
        }
        $this->classes = array_unique($classes);
        $this->cacheDir = $cacheDir;
        if ($this->cacheDir && !is_dir($this->cacheDir)) {
            throw  new \InvalidArgumentException(sprintf(
                'Cache directory "%s" does not exist',
                $this->cacheDir
            ));
        }
    }

    public function generateCache(): void
    {
        if (!$this->cacheIsEnabled()) {
            throw new \LogicException('Cache is not enabled, if you want to enable it, please set the cacheDir on the constructor');
        }
        $this->collect();
    }

    public function clearCache(): void
    {
        if (!$this->cacheIsEnabled()) {
            throw new \LogicException('Cache is not enabled, if you want to enable it, please set the cacheDir on the constructor');
        }

        foreach ($this->classes as $class) {
            $cacheFile = $this->getCacheFile($class);
            if (file_exists($cacheFile)) {
                unlink($cacheFile);
            }
        }
    }

    /**
     * @return array<\PhpDevCommunity\Route
     * @throws \ReflectionException
     */
    public function collect(): array
    {
        $routes = [];
        foreach ($this->classes as $class) {
            $routes = array_merge($routes, $this->getRoutes($class));
        }
        return $routes;
    }


    private function getRoutes(string $class): array
    {
        if ($this->cacheIsEnabled() && (  $cached = $this->get($class))) {
            return $cached;
        }
        $refClass  = new \ReflectionClass($class);
        $routes = [];

        $controllerAttr = $refClass->getAttributes(
            ControllerRoute::class,
            \ReflectionAttribute::IS_INSTANCEOF
        )[0] ?? null;
        $controllerRoute = $controllerAttr ? $controllerAttr->newInstance() : new ControllerRoute('');
        foreach ($refClass->getMethods() as $method) {
            foreach ($method->getAttributes(
                Route::class,
                \ReflectionAttribute::IS_INSTANCEOF
            ) as $attr) {
                /**
                 * @var Route $instance
                 */
                $instance = $attr->newInstance();
                $route = new \PhpDevCommunity\Route(
                    $instance->getName(),
                    $controllerRoute->getPath().$instance->getPath(),
                    [$class, $method->getName()],
                    $instance->getMethods()
                );

                $route->format($instance->getFormat() ?: $controllerRoute->getFormat());
                foreach ($instance->getOptions() as $key => $value) {
                    if (!str_starts_with($key, 'where') || $key === 'where') {
                        throw new \InvalidArgumentException(
                            'Invalid option "' . $key . '". Options must start with "where".'
                        );
                    }
                    if (is_array($value)) {
                        $route->$key(...$value);
                        continue;
                    }
                    $route->$key($value);
                }
                $routes[$instance->getName()] = $route;
            }
        }
        $routes = array_values($routes);
        if ($this->cacheIsEnabled()) {
            $this->set($class, $routes);
        }

        return $routes;

    }

    private function cacheIsEnabled(): bool
    {
        return $this->cacheDir !== null;
    }

    private function get(string $class): ?array
    {
        $cacheFile = $this->getCacheFile($class);
        if (!is_file($cacheFile)) {
            return null;
        }

        return require $cacheFile;
    }

    private function set(string $class, array $routes): void
    {
        $cacheFile = $this->getCacheFile($class);
        $content = "<?php\n\nreturn " . var_export($routes, true) . ";\n";
        file_put_contents($cacheFile, $content);
    }

    private function getCacheFile(string $class): string
    {
        return $this->cacheDir . '/' .md5($class) . '.php';
    }
}