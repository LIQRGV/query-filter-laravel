<?php

namespace Tests\LIQRGV\QueryFilter;

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Facade;
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

    function testFilter()
    {
        $controller = new MockModelController();
        $route = new Route('GET', 'some_model', []);
        $route->controller = $controller;

        $request = new Request();
        $request->query = new ParameterBag([
            "filter" => [
                "x" => [
                    "is" => 1
                ],
            ],
        ]);
        $request->setRouteResolver(function () use ($route) {
            return $route;
        });

        $route->bind($request);

        $requestParserOptions = [
            'model_namespaces' => [
                'LIQRGV\QueryFilter\Mocks',
            ]
        ];

        // somehow Config facade doesn't have `set` method.
        // See: Illuminate\Support\Fluent
        Config::request_parser($requestParserOptions);
        $requestParser = new RequestParser($request);
        $builder = $requestParser->getBuilder();

        $this->assertEquals("select * from \"mock_models\" where \"x\" = ?", $builder->toSql());
        $this->assertEquals([1], $builder->getBindings());
    }
}
