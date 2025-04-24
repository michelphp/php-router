<?php

namespace Test\PhpDevCommunity;

use InvalidArgumentException;
use PhpDevCommunity\Route;
use PhpDevCommunity\UniTester\TestCase;
use stdClass;

class RouteTest extends TestCase
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
        $this->testMatchRoute();
        $this->testNotMatchRoute();
        $this->testException();
        $this->testWheres();
        $this->testWhereDate();
        $this->testWhereYearMonth();
        $this->testWhereEmail();
        $this->testWhereUuid();
        $this->testWhereBool();
        $this->whereAnything();
    }

    public function testNotMatchRoute()
    {
        $routeWithoutAttribute = new Route('view_articles', '/view/article/', ['App\\Controller\\HomeController', 'home']);
        $routeWithAttribute = new Route('view_article', '/view/article/{article}', ['App\\Controller\\HomeController', 'home']);

        $this->assertFalse($routeWithoutAttribute->match('/view/article/1'));
        $this->assertFalse($routeWithAttribute->match('/view/article/'));
    }

    public function testMatchRoute()
    {
        $routeWithAttribute = new Route('view_article', '/view/article/{article}', ['App\\Controller\\HomeController', 'home']);
        $routeWithAttributes = new Route('view_article_page', '/view/article/{article}/{page}', ['App\\Controller\\HomeController', 'home']);
        $routeWithoutAttribute = new Route('view_articles', '/view/article', ['App\\Controller\\HomeController', 'home']);

        $this->assertTrue($routeWithAttribute->match('/view/article/1'));
        $this->assertTrue($routeWithAttributes->match('/view/article/1/24'));
        $this->assertTrue($routeWithoutAttribute->match('/view/article/'));
    }

    public function testException()
    {
        $this->expectException(InvalidArgumentException::class, function () {
            new Route('view_articles', '/view', ['App\\Controller\\HomeController', 'home'], []);
        });
    }

    public function testWheres()
    {
        $routes = [
            Route::get('blog.show', '/blog/{id}', function () {
            })->whereNumber('id'),
            Route::get('blog.show', '/blog/{slug}', function () {
            })->whereSlug('slug'),
            Route::get('blog.show', '/blog/{slug}/{id}', function () {
            })
                ->whereNumber('id')
                ->whereSlug('slug'),
            Route::get('invoice.show', '/invoice/{number}', function () {
            })->whereAlphaNumeric('number'),
            Route::get('invoice.show', '/invoice/{number}', function () {
            })->whereAlpha('number'),
            Route::get('invoice.with.slash', '/invoice/{slash*}', function () {
            }),
            Route::get('invoice.with.slash', '/invoice/{slash}', function () {
            })->whereTwoSegments('slash'),
        ];


        $route = $routes[0];
        $this->assertTrue($route->match('/blog/1'));
        $this->assertStrictEquals(['id' => '1'], $route->getAttributes());
        $this->assertFalse($route->match('/blog/F1'));

        $route = $routes[1];
        $this->assertTrue($route->match('/blog/title-of-article'));
        $this->assertStrictEquals(['slug' => 'title-of-article'], $route->getAttributes());
        $this->assertFalse($routes[1]->match('/blog/title_of_article'));

        $route = $routes[2];
        $this->assertTrue($routes[2]->match('/blog/title-of-article/12'));
        $this->assertStrictEquals(['slug' => 'title-of-article', 'id' => '12'], $route->getAttributes());

        $route = $routes[3];
        $this->assertTrue($route->match('/invoice/F0004'));
        $this->assertStrictEquals(['number' => 'F0004'], $route->getAttributes());

        $route = $routes[4];
        $this->assertFalse($routes[4]->match('/invoice/F0004'));
        $this->assertTrue($routes[4]->match('/invoice/FROUIAUI'));
        $this->assertStrictEquals(['number' => 'FROUIAUI'], $route->getAttributes());

        $route = $routes[5];
        $this->assertTrue($route->match('/invoice/FROUIAUI/12/24-25'));
        $this->assertStrictEquals(['slash' => 'FROUIAUI/12/24-25'], $route->getAttributes());

        $route = $routes[6];
        $this->assertFalse($route->match('/invoice/FROUIAUI/12/24-25'));
        $this->assertTrue($route->match('/invoice/FROUIAUI/toto'));
        $this->assertStrictEquals(['slash' => 'FROUIAUI/toto'], $route->getAttributes());
    }

    public function testWhereDate()
    {
        $route = Route::get('example', '/example/{date}', function () {
        })->whereDate('date');
        $this->assertTrue($route->match('/example/2022-12-31'));
        $this->assertFalse($route->match('/example/12-31-2022'));
        $this->assertFalse($route->match('/example/2022-13'));
    }

    public function testWhereYearMonth()
    {
        $route = Route::get('example', '/example/{yearMonth}', function () {
        })->whereYearMonth('yearMonth');
        $this->assertTrue($route->match('/example/2022-12'));
        $this->assertFalse($route->match('/example/12-31-2022'));
        $this->assertFalse($route->match('/example/2022-13-10'));
    }

    public function testWhereEmail()
    {
        $route = Route::get('example', '/example/{email}/{email2}', function () {
        })->whereEmail('email', 'email2');
        $this->assertTrue($route->match('/example/0L5yT@example.com/0L5yT@example.com'));
        $this->assertFalse($route->match('/example/@example.com/0L5yT@example.com'));
        $this->assertFalse($route->match('/example/0L5yT@example.com/toto'));
    }

    public function testWhereUuid()
    {
        $route = Route::get('example', '/example/{uuid}', function () {
        })->whereEmail('uuid');
        $route->whereUuid('uuid');

        $this->assertTrue($route->match('/example/123e4567-e89b-12d3-a456-426614174000'));

        $this->assertFalse($route->match('/example/123e4567-e89b-12d3-a456-42661417400z'));
        $this->assertFalse($route->match('/example/invalid-uuid'));

        $route = Route::get('example', '/example/{uuid}/unused', function () {
        })->whereEmail('uuid');
        $route->whereUuid('uuid');

        $this->assertFalse($route->match('/example/123e4567-e89b-12d3-a456-426614174000'));
        $this->assertTrue($route->match('/example/123e4567-e89b-12d3-a456-426614174000/unused'));

        $this->assertFalse($route->match('/example/123e4567-e89b-12d3-a456-42661417400z/unused'));
        $this->assertFalse($route->match('/example/invalid-uuid/unused'));
    }

    public function testWhereBool()
    {
        $route = Route::get('example', '/example/{bool}', function () {
        })->whereBool('bool');
        $this->assertTrue($route->match('/example/true'));
        $this->assertTrue($route->match('/example/1'));
        $this->assertTrue($route->match('/example/false'));
        $this->assertTrue($route->match('/example/0'));
        $this->assertFalse($route->match('/example/invalid'));

    }

    private function whereAnything()
    {
        $route = Route::get('example', '/example/{anything}', function () {
        })->whereAnything('anything');
        $this->assertTrue($route->match('/example/anything'));
        $this->assertTrue($route->match('/example/anything/anything'));
        $this->assertTrue($route->match('/example/anything/anything/anything'));
        $base64 = $this->generateComplexString();
        $this->assertTrue($route->match('/example/' . $base64));
        $this->assertStrictEquals(['anything' => $base64], $route->getAttributes());

    }

    private function generateComplexString(): string
    {
        $characters = 'ABCDEFGHIJKLMklmnopqrstuvwxyz0123456789!@#$%^&*()-_=+[{]}\\|;:\'",<.>/?`~';
        $complexString = '';
        for ($i = 0; $i < 200; $i++) {
            $complexString .= $characters[random_int(0, strlen($characters) - 1)];
        }
        $complexString .= '-' . time();
        return $complexString;
    }
}
