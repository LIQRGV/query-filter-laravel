<?php

namespace Tests\LIQRGV\QueryFilter;

use Illuminate\Database\Query\Builder;
use LIQRGV\QueryFilter\Mocks\MockModelController;
use LIQRGV\QueryFilter\Mocks\RelationMocks\MockModelWithRelationOne;
use LIQRGV\QueryFilter\RequestParser;
use Symfony\Component\HttpFoundation\ParameterBag;

class RequestParserIncludeEagerLoadTest extends TestCase
{
    function testIncludeEagerLoadRelation()
    {
        $uri = 'some_model';
        $controllerClass = MockModelController::class;
        $query = new ParameterBag([
            "include" => "mockModel",
        ]);
        $requestParserOptions = [
        ];

        $request = $this->createControllerRequest($uri, $controllerClass, $query, $requestParserOptions);

        $requestParser = new RequestParser($request);
        $requestParser->setModel(MockModelWithRelationOne::class);
        $builder = $requestParser->getBuilder();

        $query = $builder->getQuery();
        $this->assertEquals("mock_model_with_relation_ones", $query->from);
        $this->assertArrayHasKey("mockModel", $builder->getEagerLoads());
    }
}
