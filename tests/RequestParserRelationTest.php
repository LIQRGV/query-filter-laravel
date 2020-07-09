<?php

namespace Tests\LIQRGV\QueryFilter;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Config;
use LIQRGV\QueryFilter\Exception\ModelNotFoundException;
use LIQRGV\QueryFilter\Exception\NotModelException;
use LIQRGV\QueryFilter\Mocks\MockModelController;
use LIQRGV\QueryFilter\Mocks\RelationMocks\MockModelWithRelationOne;
use LIQRGV\QueryFilter\RequestParser;
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
        $this->assertEquals("Column", $builder->getQuery()->wheres[0]["query"]->wheres[0]["type"]);
        $this->assertEquals("mock_model_with_relation_ones.id", $builder->getQuery()->wheres[0]["query"]->wheres[0]["first"]);
        $this->assertEquals("=", $builder->getQuery()->wheres[0]["query"]->wheres[0]["operator"]);
        $this->assertEquals("mock_models.mock_model_with_relation_one_id", $builder->getQuery()->wheres[0]["query"]->wheres[0]["second"]);
        $this->assertEquals("and", $builder->getQuery()->wheres[0]["query"]->wheres[0]["boolean"]);

        // assert relation query
        $this->assertEquals("Basic", $builder->getQuery()->wheres[0]["query"]->wheres[1]["type"]);
        $this->assertEquals("id", $builder->getQuery()->wheres[0]["query"]->wheres[1]["column"]);
        $this->assertEquals("=", $builder->getQuery()->wheres[0]["query"]->wheres[1]["operator"]);
        $this->assertEquals("2", $builder->getQuery()->wheres[0]["query"]->wheres[1]["value"]);
        $this->assertEquals("and", $builder->getQuery()->wheres[0]["query"]->wheres[1]["boolean"]);
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
        $this->assertEquals("Nested", $builder->getQuery()->wheres[0]["type"]);
        $this->assertEquals("and", $builder->getQuery()->wheres[0]["boolean"]);

        /** @var Builder $nestedQuery */
        $nestedQuery = $builder->getQuery()->wheres[0]["query"];

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
        $this->assertEquals("id", $firstInnerQuery->wheres[1]["column"]);
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
        $this->assertEquals("other_attr", $secondInnerQuery->wheres[1]["column"]);
        $this->assertEquals("=", $secondInnerQuery->wheres[1]["operator"]);
        $this->assertEquals("2", $secondInnerQuery->wheres[1]["value"]);
        $this->assertEquals("and", $secondInnerQuery->wheres[1]["boolean"]);
    }
}
