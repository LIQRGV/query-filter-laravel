<?php

namespace LIQRGV\QueryFilter;

use Illuminate\Database\Query\Builder;
use LIQRGV\QueryFilter\Mocks\MockModelController;
use LIQRGV\QueryFilter\Mocks\RelationMocks\MockModelWithRelationOne;
use Symfony\Component\HttpFoundation\ParameterBag;

class RequestParserRelationTest extends TestCase
{
    function testFilterRelationOne()
    {
        $uri = 'some_model';
        $controllerClass = MockModelController::class;
        $query = new ParameterBag([
            "filter" => [
                "mockModel.id" => [
                    "is" => 2
                ]
            ]
        ]);
        $requestParserOptions = [
        ];

        $request = $this->createControllerRequest($uri, $controllerClass, $query, $requestParserOptions);

        $requestParser = new RequestParser($request);
        $requestParser->setModel(MockModelWithRelationOne::class);
        $builder = $requestParser->getBuilder();

        $query = $builder->getQuery();
        $this->assertEquals("mock_model_with_relation_ones", $query->from);

        // assert relation first
        $firstQueryWhere = $builder->getQuery()->wheres[0]["query"]->wheres[0];
        $this->assertEquals("Column", $firstQueryWhere["type"]);
        $this->assertEquals("mock_model_with_relation_ones.id", $firstQueryWhere["first"]);
        $this->assertEquals("=", $firstQueryWhere["operator"]);
        $this->assertEquals("mock_models.mock_model_with_relation_one_id", $firstQueryWhere["second"]);
        $this->assertEquals("and", $firstQueryWhere["boolean"]);

        // assert relation query
        $secondQueryWhere = $builder->getQuery()->wheres[0]["query"]->wheres[1];
        $this->assertEquals("Basic", $secondQueryWhere["type"]);
        $this->assertEquals("mock_models.id", $secondQueryWhere["column"]);
        $this->assertEquals("=", $secondQueryWhere["operator"]);
        $this->assertEquals("2", $secondQueryWhere["value"]);
        $this->assertEquals("and", $secondQueryWhere["boolean"]);
    }

    function testFilterOrRelationOne()
    {
        $uri = 'some_model';
        $controllerClass = MockModelController::class;
        $query = new ParameterBag([
            "filter" => [
                "mockModel.id|mockModel.other_attr" => [
                    "is" => 2
                ]
            ]
        ]);
        $requestParserOptions = [];

        $request = $this->createControllerRequest($uri, $controllerClass, $query, $requestParserOptions);

        $requestParser = new RequestParser($request);
        $requestParser->setModel(MockModelWithRelationOne::class);
        $builder = $requestParser->getBuilder();

        $query = $builder->getQuery();
        $this->assertEquals("mock_model_with_relation_ones", $query->from);

        // assert nested query
        $firstQueryWhere = $builder->getQuery()->wheres[0];
        $this->assertEquals("Nested", $firstQueryWhere["type"]);
        $this->assertEquals("and", $firstQueryWhere["boolean"]);

        /** @var Builder $nestedQuery */
        $nestedQuery = $firstQueryWhere["query"];

        $this->assertEquals("Exists", $nestedQuery->wheres[0]["type"]);
        $this->assertEquals("Exists", $nestedQuery->wheres[1]["type"]);
        $this->assertEquals("or", $nestedQuery->wheres[0]["boolean"]);
        $this->assertEquals("or", $nestedQuery->wheres[1]["boolean"]);

        $firstInnerQuery = $nestedQuery->wheres[0]["query"];
        $secondInnerQuery = $nestedQuery->wheres[1]["query"];

        // assert first relation
        $this->assertEquals("Column", $firstInnerQuery->wheres[0]["type"]);
        $this->assertEquals("mock_model_with_relation_ones.id", $firstInnerQuery->wheres[0]["first"]);
        $this->assertEquals("=", $firstInnerQuery->wheres[0]["operator"]);
        $this->assertEquals("mock_models.mock_model_with_relation_one_id", $firstInnerQuery->wheres[0]["second"]);
        $this->assertEquals("and", $firstInnerQuery->wheres[0]["boolean"]);

        // assert first relation query field
        $this->assertEquals("Basic", $firstInnerQuery->wheres[1]["type"]);
        $this->assertEquals("mock_models.id", $firstInnerQuery->wheres[1]["column"]);
        $this->assertEquals("=", $firstInnerQuery->wheres[1]["operator"]);
        $this->assertEquals("2", $firstInnerQuery->wheres[1]["value"]);
        $this->assertEquals("and", $firstInnerQuery->wheres[1]["boolean"]);

        // assert second relation
        $this->assertEquals("Column", $secondInnerQuery->wheres[0]["type"]);
        $this->assertEquals("mock_model_with_relation_ones.id", $secondInnerQuery->wheres[0]["first"]);
        $this->assertEquals("=", $secondInnerQuery->wheres[0]["operator"]);
        $this->assertEquals("mock_models.mock_model_with_relation_one_id", $secondInnerQuery->wheres[0]["second"]);
        $this->assertEquals("and", $secondInnerQuery->wheres[0]["boolean"]);

        // assert second relation query field
        $this->assertEquals("Basic", $secondInnerQuery->wheres[1]["type"]);
        $this->assertEquals("mock_models.other_attr", $secondInnerQuery->wheres[1]["column"]);
        $this->assertEquals("=", $secondInnerQuery->wheres[1]["operator"]);
        $this->assertEquals("2", $secondInnerQuery->wheres[1]["value"]);
        $this->assertEquals("and", $secondInnerQuery->wheres[1]["boolean"]);
    }
}
