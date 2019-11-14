<?php

namespace Tests\LIQRGV\QueryFilter;

use LIQRGV\QueryFilter\RequestParser;
use Symfony\Component\HttpFoundation\ParameterBag;

class RequestParserLumenTest extends TestCase
{
    function testFilterRouteIsArray()
    {
        $uri = 'mock_some_model';
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

        $this->assertEquals("select * from \"mock_some_models\" where \"x\" = ?", $builder->toSql());
        $this->assertEquals([1], $builder->getBindings());
    }
}
