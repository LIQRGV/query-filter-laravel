<?php

namespace Tests\LIQRGV\QueryFilter;

use LIQRGV\QueryFilter\Mocks\MockLumenModel;
use LIQRGV\QueryFilter\Mocks\MockLumenModelController;
use LIQRGV\QueryFilter\Mocks\MockModelController;
use LIQRGV\QueryFilter\RequestParser;
use Symfony\Component\HttpFoundation\ParameterBag;

class RequestParserLumenTest extends TestCase
{
    function testFilterRouteIsArray()
    {
        $uri = 'mock_some_model';
        $routeResolverResult = [
            'uses' => MockLumenModelController::class . '@' . 'index',
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
        $this->assertEquals("mock_lumen_models", $query->from);
        $this->assertEquals("x", $query->wheres[0]['column']);
        $this->assertEquals("=", $query->wheres[0]['operator']);
        $this->assertEquals([1], $builder->getBindings());
    }
}
