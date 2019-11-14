<?php

namespace Tests\LIQRGV\QueryFilter;

use LIQRGV\QueryFilter\Mocks\MockModelController;
use LIQRGV\QueryFilter\RequestParser;
use Symfony\Component\HttpFoundation\ParameterBag;

class RequestParserLaravelTest extends TestCase
{
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

        $query = $builder->getQuery();
        $this->assertEquals("mock_models", $query->from);
        $this->assertEquals("x", $query->wheres[0]['column']);
        $this->assertEquals("=", $query->wheres[0]['operator']);
        $this->assertEquals([1], $builder->getBindings());
    }

    function testFilterNormalViaClosure()
    {
        $uri = 'mock_some_model';
        $routeResolverResult = [
            'uses' => MockModelController::class . '@' . 'index',
        ];
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

        $request = $this->createRequestWithRouteArray($uri, $routeResolverResult, $query, $requestParserOptions);

        $requestParser = new RequestParser($request);
        $builder = $requestParser->getBuilder();

        $query = $builder->getQuery();
        $this->assertEquals("mock_models", $query->from);
        $this->assertEquals("x", $query->wheres[0]['column']);
        $this->assertEquals("=", $query->wheres[0]['operator']);
        $this->assertEquals([1], $builder->getBindings());
    }
}
