<?php

declare(strict_types=1);

namespace PhpDevCommunity;

use PhpDevCommunity\Exception\MethodNotAllowed;
use PhpDevCommunity\Exception\RouteNotFound;
use Psr\Http\Message\ServerRequestInterface;

final class Router implements RouterInterface
{
    private const NO_ROUTE = 404;
    private const METHOD_NOT_ALLOWED = 405;
    private \ArrayObject $routes;
    private UrlGenerator $urlGenerator;

    /**
     * Router constructor.
     * @param array<Route> $routes The routes to initialize the Router with.
     * @param string $defaultUri The default URI for the Router.
     */
    public function __construct(array $routes = [], string $defaultUri = 'http://localhost')
    {
        $this->routes = new \ArrayObject();
        $this->urlGenerator = new UrlGenerator($this->routes, $defaultUri);
        foreach ($routes as $route) {
            $this->add($route);
        }
    }



    /**
     * Add a Route to the collection.
     *
     * @param Route $route The Route to add
     * @return self
     */
    public function add(Route $route): self
    {
        $this->routes->offsetSet($route->getName(), $route);
        return $this;
    }

    /**
     * Matches a server request to a route based on the request's URI and method.
     *
     * @param ServerRequestInterface $serverRequest The server request to match.
     * @return Route The matched route.
     * @throws MethodNotAllowed Method Not Allowed : $method
     * * @throws RouteNotFound No route found for $path
     */
    public function match(ServerRequestInterface $serverRequest): Route
    {
        return $this->matchFromPath($serverRequest->getUri()->getPath(), $serverRequest->getMethod());
    }

    /**
     * Match a route from the given path and method.
     *
     * @param string $path The path to match
     * @param string $method The HTTP method
     * @throws MethodNotAllowed Method Not Allowed : $method
     * @throws RouteNotFound No route found for $path
     * @return Route
     */
    public function matchFromPath(string $path, string $method): Route
    {
        /**
         * @var Route $route
         */
        $routeMatchedButMethodNotAllowed = false;
        foreach ($this->routes as $route) {
            if ($route->match($path) === false) {
                continue;
            }

            if (!in_array($method, $route->getMethods())) {
                $routeMatchedButMethodNotAllowed = true;
                continue;
            }
            return $route;
        }

        if ($routeMatchedButMethodNotAllowed) {
            throw new MethodNotAllowed(
                'Method Not Allowed : ' . $method,
                self::METHOD_NOT_ALLOWED
            );
        }

        throw new RouteNotFound(
            'No route found for ' . $path,
            self::NO_ROUTE
        );
    }

    /**
     * Generate a URI based on the provided name, parameters, and settings.
     *
     * @param string $name The name used for generating the URI.
     * @param array $parameters An array of parameters to be included in the URI.
     * @param bool $absoluteUrl Whether the generated URI should be an absolute URL.
     * @return string The generated URI.
     */
    public function generateUri(string $name, array $parameters = [], bool $absoluteUrl = false): string
    {
        return $this->urlGenerator->generate($name, $parameters, $absoluteUrl);
    }

    public function getUrlGenerator(): UrlGenerator
    {
        return $this->urlGenerator;
    }
}
