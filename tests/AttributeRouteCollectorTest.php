<?php

namespace Test\PhpDevCommunity;

use PhpDevCommunity\Route;
use PhpDevCommunity\UniTester\TestCase;

class AttributeRouteCollectorTest extends TestCase
{

    protected function setUp(): void
    {
        // TODO: Implement setUp() method.
    }

    protected function tearDown(): void
    {
        // TODO: Implement tearDown() method.
    }

    protected function execute(): void
    {
        if (PHP_VERSION_ID < 80000) {
            return;
        }
        $attributeRouteCollector = new \PhpDevCommunity\Attribute\AttributeRouteCollector([
            'Test\PhpDevCommunity\Controller\UserController',
            'Test\PhpDevCommunity\Controller\ProductController',
            'Test\PhpDevCommunity\Controller\ApiController',
            'Test\PhpDevCommunity\Controller\PingController'
        ]);
        $routes = $attributeRouteCollector->collect();
        $this->assertStrictEquals(9, count($routes));

        $attributeRouteCollector = new \PhpDevCommunity\Attribute\AttributeRouteCollector([
            'Test\PhpDevCommunity\Controller\UserController'
        ]);
        $routes = $attributeRouteCollector->collect();
        $this->assertStrictEquals(3, count($routes));
        $this->assertStrictEquals('user_list', $routes[0]->getName());
        $this->assertEquals(['GET', 'HEAD'], $routes[0]->getMethods());

        $this->assertStrictEquals('user_show', $routes[1]->getName());
        $this->assertEquals(['GET', 'HEAD'], $routes[1]->getMethods());

        $this->assertStrictEquals('user_create', $routes[2]->getName());
        $this->assertEquals(['POST'], $routes[2]->getMethods());


        $attributeRouteCollector = new \PhpDevCommunity\Attribute\AttributeRouteCollector([
            'Test\PhpDevCommunity\Controller\PingController'
        ]);
        $routes = $attributeRouteCollector->collect();
        $this->assertStrictEquals(1, count($routes));
        $this->assertStrictEquals('/api/ping', $routes[0]->getPath());
        $this->assertEquals(['GET', 'HEAD'], $routes[0]->getMethods());
        $this->assertEquals('json', $routes[0]->getFormat());


        $this->testCache();
    }

    private function testCache(): void
    {
        $controllers = [
            'Test\PhpDevCommunity\Controller\UserController',
            'Test\PhpDevCommunity\Controller\ProductController',
            'Test\PhpDevCommunity\Controller\ApiController',
            'Test\PhpDevCommunity\Controller\PingController'
        ];

        $cacheDir = dirname(__FILE__) . '/cache';
        if (is_dir($cacheDir)) {
            rmdir($cacheDir);
        }
        mkdir($cacheDir, 0777, true);

        $attributeRouteCollector = new \PhpDevCommunity\Attribute\AttributeRouteCollector($controllers, $cacheDir);

        $attributeRouteCollector->generateCache();
        $this->assertTrue(is_dir($cacheDir));
        foreach ($controllers as $controller) {
            $cacheFile = $cacheDir . '/' . md5($controller) . '.php';
            $this->assertTrue($cacheFile);
        }
        $routes = $attributeRouteCollector->collect();
        $this->assertStrictEquals(9, count($routes));
        foreach ($routes as $route) {
            $this->assertInstanceOf(Route::class, $route);
        }

        $attributeRouteCollector->clearCache();
        rmdir($cacheDir);
    }
}