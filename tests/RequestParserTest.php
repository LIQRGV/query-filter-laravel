<?php

namespace Tests\LIQRGV\QueryFilter;

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Facade;
use LIQRGV\QueryFilter\Exception\ModelNotFoundException;
use LIQRGV\QueryFilter\Exception\NotModelException;
use LIQRGV\QueryFilter\Mocks\MockModelController;
use LIQRGV\QueryFilter\RequestParser;
use Symfony\Component\HttpFoundation\ParameterBag;

class RequestParserTest extends TestCase
{
    function setUp()
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

    function testRequestViaController()
    {
        $this->markTestSkipped('Test for debug purpose only, change createModelBuilderStruct modifier to public to use');
        $controller = new MockModelController();
        $route = new Route('GET', 'some_model', []);
        $route->controller = $controller;

        $request = new Request();
        $request->setRouteResolver(function () use ($route) {
            return $route;
        });

        $route->bind($request);

        $requestParserOptions = [
            'model_namespaces' => [
                'LIQRGV\QueryFilter\Mocks',
            ]
        ];

        Config::request_parser($requestParserOptions);
        $requestParser = new RequestParser($request);
        $builderStruct = $requestParser->createModelBuilderStruct($request);
        $this->assertEquals('LIQRGV\QueryFilter\Mocks\MockModel', $builderStruct->baseModelName);
    }

    function testRequestViaClosure()
    {
        $this->markTestSkipped('Test for debug purpose only, change createModelBuilderStruct modifier to public to use');
        $route = new Route('GET', 'mock_closure_model', []);

        $request = new Request();
        $request->setRouteResolver(function () use ($route) {
            return $route;
        });

        $route->bind($request);

        $requestParserOptions = [
            'model_namespaces' => [
                'LIQRGV\QueryFilter\Mocks',
            ]
        ];

        Config::request_parser($requestParserOptions);
        $requestParser = new RequestParser($request);
        $builderStruct = $requestParser->createModelBuilderStruct($request);
        $this->assertEquals('LIQRGV\QueryFilter\Mocks\MockClosureModel', $builderStruct->baseModelName);
    }

    function testFilterNormalViaController()
    {
        $uri = 'some_model';
        $controllerClass = MockModelController::class;
        $query = new ParameterBag([
            "filter" => [
                "x" => [
                    "is" => 1
                ]
            ],
        ]);
        // emulate config on config/request_parser.php
        $requestParserOptions = [
            'model_namespaces' => [
                'LIQRGV\QueryFilter\Mocks',
            ]
        ];

        $request = $this->createControllerRequest($uri, $controllerClass, $query, $requestParserOptions);

        $requestParser = new RequestParser($request);
        $builder = $requestParser->getBuilder();

        $this->assertEquals("select * from \"mock_models\" where \"x\" = ?", $builder->toSql());
        $this->assertEquals([1], $builder->getBindings());
    }

    function testFilterNormalViaClosure()
    {
        $uri = 'some_model';
        $query = new ParameterBag([
            "filter" => [
                "x" => [
                    "is" => 1
                ]
            ],
        ]);
        $requestParserOptions = [
            'model_namespaces' => [
                'LIQRGV\QueryFilter\Mocks',
            ]
        ];

        $request = $this->createClosureRequest($uri, $query, $requestParserOptions);

        $requestParser = new RequestParser($request);
        $builder = $requestParser->getBuilder();

        $this->assertEquals("select * from \"some_models\" where \"x\" = ?", $builder->toSql());
        $this->assertEquals([1], $builder->getBindings());
    }

    function testFilterKeywordIn()
    {
        $uri = 'some_model';
        $controllerClass = MockModelController::class;
        $query = new ParameterBag([
            "filter" => [
                "y" => [
                    "in" => "2,3,4"
                ]
            ],
        ]);
        $requestParserOptions = [
            'model_namespaces' => [
                'LIQRGV\QueryFilter\Mocks',
            ]
        ];

        $request = $this->createControllerRequest($uri, $controllerClass, $query, $requestParserOptions);

        $requestParser = new RequestParser($request);
        $builder = $requestParser->getBuilder();

        $this->assertEquals("select * from \"mock_models\" where \"y\" in (?, ?, ?)", $builder->toSql());
        $this->assertEquals(['2', '3', '4'], $builder->getBindings());
    }

    function testNoModel()
    {
        $this->expectException(ModelNotFoundException::class);
        $uri = 'non_exists_model';
        $query = new ParameterBag([
            "filter" => [
                "y" => [
                    "in" => "2,3,4"
                ]
            ],
        ]);
        $requestParserOptions = [];

        $request = $this->createClosureRequest($uri, $query, $requestParserOptions);

        $requestParser = new RequestParser($request);
        var_dump($requestParser->getBuilder());
    }

    function testTargetNotModel()
    {
        $this->expectException(NotModelException::class);
        $uri = 'mock_not_model';
        $query = new ParameterBag([]);
        $requestParserOptions = [
            'model_namespaces' => [
                'LIQRGV\QueryFilter\Mocks',
            ]
        ];

        $request = $this->createClosureRequest($uri, $query, $requestParserOptions);

        $requestParser = new RequestParser($request);
        $requestParser->getBuilder();
    }

    private function createControllerRequest($uri, $controllerClass, $query, $requestParserOptions)
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

    private function createClosureRequest($uri, $query, $requestParserOptions)
    {
        $route = new Route('GET', $uri, []);

        $request = new Request();
        $request->query = $query;
        $request->setRouteResolver(function () use ($route) {
            return $route;
        });

        $route->bind($request);

        Config::request_parser($requestParserOptions);

        return $request;
    }
}
