<?php

namespace Tests\LIQRGV\QueryFilter;

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Facade;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $capsule = new Capsule();
        $capsule->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $capsule->setEventDispatcher(new Dispatcher(new Container));
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        Facade::setFacadeApplication($capsule->getContainer());
    }

    protected function createControllerRequest($uri, $controllerClass, $query, $requestParserOptions)
    {
        $route = new Route('GET', $uri, []);

        $controller = new $controllerClass();
        $route->controller = $controller;

        $request = new Request();
        $request->query = $query;
        $request->setRouteResolver(function () use ($route) {
            return $route;
        });

        $route->bind($request);

        // somehow Config facade doesn't have `set` method.
        // See: Illuminate\Support\Fluent
        Config::request_parser($requestParserOptions);

        return $request;
    }

    protected function createClosureRequest($uri, $query, $requestParserOptions)
    {
        $route = new Route('GET', $uri, []);

        $serverParam = [
            'REQUEST_URI' => $uri,
        ];

        $request = new Request([], [], [], [], [], $serverParam);
        $request->query = $query;
        $request->setRouteResolver(function () use ($route) {
            return $route;
        });

        $route->bind($request);

        Config::request_parser($requestParserOptions);

        return $request;
    }

    protected function createRequestWithRouteArray($uri, $routeResolverResult, $query, $requestParserOptions)
    {
        $serverParam = [
            'REQUEST_URI' => $uri,
        ];

        $request = new Request([], [], [], [], [], $serverParam);
        $request->query = $query;
        $request->setRouteResolver(function () use ($routeResolverResult) {
            return [
                true, $routeResolverResult, []
            ];
        });

        Config::request_parser($requestParserOptions);

        return $request;
    }
}

