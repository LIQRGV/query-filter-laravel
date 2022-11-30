<?php

namespace LIQRGV\QueryFilter;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Config;
use LIQRGV\QueryFilter\Exception\ModelNotFoundException;
use LIQRGV\QueryFilter\Exception\NotModelException;
use LIQRGV\QueryFilter\Mocks\MockClosureModel;
use LIQRGV\QueryFilter\Mocks\MockModel;
use LIQRGV\QueryFilter\Mocks\MockModelController;
use Symfony\Component\HttpFoundation\ParameterBag;

class RequestParserTest extends TestCase
{
    function testRequestViaController()
    {
        $this->markTestSkipped('Test for debug purpose only, change createModelBuilderStruct modifier to public to use');
        $controller = new MockModelController();
        $route = new Route('GET', 'mock_some_model', []);
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
        $this->assertEquals(MockModel::class, $builderStruct->baseModelName);
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
        $this->assertEquals(MockClosureModel::class, $builderStruct->baseModelName);
    }

    function testModelSetter()
    {
        $uri = 'some_model';
        $controllerClass = MockModelController::class;
        $query = new ParameterBag([
            "sort" => "-name",
        ]);
        $requestParserOptions = [
            'model_namespaces' => [
                'LIQRGV\QueryFilter\Mocks',
            ]
        ];

        $request = $this->createControllerRequest($uri, $controllerClass, $query, $requestParserOptions);

        $requestParser = new RequestParser($request);
        $requestParser->setModel(MockModel::class);
        $builder = $requestParser->getBuilder();

        $query = $builder->getQuery();
        $this->assertEquals("mock_models", $query->from);
    }

    function testSortBy()
    {
        $uri = 'some_model';
        $controllerClass = MockModelController::class;
        $query = new ParameterBag([
            "sort" => "-name",
        ]);
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
        $this->assertEquals("name", $builder->getQuery()->orders[0]['column']);
        $this->assertEquals("desc", strtolower($builder->getQuery()->orders[0]['direction']));
    }

    function testSortByMultiple()
    {
        $uri = 'some_model';
        $controllerClass = MockModelController::class;
        $query = new ParameterBag([
            "sort" => "-name,id",
        ]);
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
        $this->assertEquals("name", $builder->getQuery()->orders[0]['column']);
        $this->assertEquals("desc", strtolower($builder->getQuery()->orders[0]['direction']));
        $this->assertEquals("id", $builder->getQuery()->orders[1]['column']);
        $this->assertEquals("asc", strtolower($builder->getQuery()->orders[1]['direction']));
    }

    function testSortByWithInvalidField()
    {
        $uri = 'some_model';
        $controllerClass = MockModelController::class;
        $query = new ParameterBag([
            "sort" => "-name-with-dash",
        ]);
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
        $this->assertEmpty($builder->getQuery()->orders);
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

        $query = $builder->getQuery();
        $this->assertEquals("mock_models", $query->from);
        $this->assertEquals("mock_models.y", $query->wheres[0]['column']);
        $this->assertEquals("in", strtolower($query->wheres[0]['type']));
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

    function testFilterWithOr()
    {
        //Create Request
        $uri = 'some_model';
        $controllerClass = MockModelController::class;
        $query = new ParameterBag([
            "filter" => [
                "x|y" => [
                    "is" => "1"
                ],
                "z" => [
                    "in" => "1,2,3"
                ]
            ]
        ]);
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

        //Assert subquery exists
        $this->assertArrayHasKey('query', $query->wheres[0]);
        $subquery = $query->wheres[0]['query'];

        //Assert subquery components
        $this->assertEquals("mock_models.x", $subquery->wheres[0]['column']);
        $this->assertEquals("=", strtolower($subquery->wheres[0]['operator']));

        $this->assertEquals("mock_models.y", $subquery->wheres[1]['column']);
        $this->assertEquals("=", strtolower($subquery->wheres[1]['operator']));
        $this->assertEquals("or", strtolower($subquery->wheres[1]['boolean']));

        //Assert second where term of the main query
        $this->assertEquals("mock_models.z", $query->wheres[1]['column']);
        $this->assertEquals("in", strtolower($query->wheres[1]['type']));

        $this->assertEquals(['1', '1', '1', '2', '3'], $builder->getBindings());
    }

    function testFilterWithOrUsingInQueryKeyword()
    {
        $uri = 'some_model';
        $controllerClass = MockModelController::class;
        $query = new ParameterBag([
            "filter" => [
                "c|d" => [
                    "in" => "1,2,3"
                ],
                "i" => [
                    "between" => "9,10"
                ]
            ]
        ]);
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

        $this->assertArrayHasKey('query', $query->wheres[0]);
        $this->assertEquals('Nested', $query->wheres[0]['type']);

        $subquery1 = $query->wheres[0]['query'];

        $this->assertEquals("mock_models.c", $subquery1->wheres[0]['column']);
        $this->assertEquals("in", strtolower($subquery1->wheres[0]['type']));

        $this->assertEquals("mock_models.d", $subquery1->wheres[1]['column']);
        $this->assertEquals("in", strtolower($subquery1->wheres[1]['type']));
        $this->assertEquals("or", strtolower($subquery1->wheres[1]['boolean']));

        $this->assertEquals("mock_models.i", $query->wheres[1]['column']);
        $this->assertEquals("between", strtolower($query->wheres[1]['type']));

        $this->assertEquals(
            [
                '1', '2', '3', '1', '2', '3',
                '9', '10'
            ],
            $builder->getBindings()
        );
    }

    function testFilterWithOrUsingNotInQueryKeyword()
    {
        $uri = 'some_model';
        $controllerClass = MockModelController::class;
        $query = new ParameterBag([
            "filter" => [
                "e|f" => [
                    "!in" => "4,5,6"
                ],
                "i" => [
                    "between" => "9,10"
                ]
            ]
        ]);
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

        $this->assertArrayHasKey('query', $query->wheres[0]);
        $this->assertEquals('Nested', $query->wheres[0]['type']);

        $subquery1 = $query->wheres[0]['query'];

        $this->assertEquals("mock_models.e", $subquery1->wheres[0]['column']);
        $this->assertEquals("NotIn", $subquery1->wheres[0]['type']);

        $this->assertEquals("mock_models.f", $subquery1->wheres[1]['column']);
        $this->assertEquals("NotIn", $subquery1->wheres[1]['type']);
        $this->assertEquals("or", strtolower($subquery1->wheres[1]['boolean']));

        $this->assertEquals("mock_models.i", $query->wheres[1]['column']);
        $this->assertEquals("between", strtolower($query->wheres[1]['type']));

        $this->assertEquals(
            [
                '4', '5', '6', '4', '5', '6',
                '9', '10'
            ],
            $builder->getBindings()
        );
    }

    function testFilterWithOrUsingBetweenQueryKeyword()
    {
        $uri = 'some_model';
        $controllerClass = MockModelController::class;
        $query = new ParameterBag([
            "filter" => [
                "g|h" => [
                    "between" => "7,8"
                ],
                "i" => [
                    "between" => "9,10"
                ]
            ]
        ]);
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

        $this->assertArrayHasKey('query', $query->wheres[0]);
        $this->assertEquals('Nested', $query->wheres[0]['type']);

        $subquery1 = $query->wheres[0]['query'];

        $this->assertEquals("mock_models.g", $subquery1->wheres[0]['column']);
        $this->assertEquals("between", strtolower($subquery1->wheres[0]['type']));

        $this->assertEquals("mock_models.h", $subquery1->wheres[1]['column']);
        $this->assertEquals("between", strtolower($subquery1->wheres[1]['type']));
        $this->assertEquals("or", strtolower($subquery1->wheres[1]['boolean']));

        $this->assertEquals("mock_models.i", $query->wheres[1]['column']);
        $this->assertEquals("between", strtolower($query->wheres[1]['type']));

        $this->assertEquals(
            [
                '7', '8', '7', '8',
                '9', '10'
            ],
            $builder->getBindings()
        );
    }

    function testPagination()
    {
        $uri = 'some_model';
        $controllerClass = MockModelController::class;
        $query = new ParameterBag([
            "limit" => "100",
            "offset" => "50"
        ]);
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
        $this->assertEquals(100, $query->limit);
        $this->assertEquals(50, $query->offset);
    }

    function testPaginationIgnoreOffset()
    {
        $uri = 'some_model';
        $controllerClass = MockModelController::class;
        $query = new ParameterBag([
            "offset" => "50"
        ]);
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
        $this->assertNull($query->limit);
        $this->assertNull($query->offset);
    }

    function testPaginationUsingZeroAsDefaultOffset()
    {
        $uri = 'some_model';
        $controllerClass = MockModelController::class;
        $query = new ParameterBag([
            "limit" => "50"
        ]);
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
        $this->assertEquals(50, $query->limit);
        $this->assertEquals(0, $query->offset);
    }

    function testFilterOnlyAllowedFilter()
    {
        $uri = 'some_model';
        $controllerClass = MockModelController::class;
        $tableName = 'mock_models';
        $ignoredKey = "ignored_key";
        $nameKey = "name";
        $valueKey = "value";
        $query = new ParameterBag([
            "filter" => [
                $nameKey => [
                    "in" => "2,3,4"
                ],
                $valueKey => [
                    "is" => "5"
                ],
                $ignoredKey => [
                    "is" => "6"
                ],
            ],
        ]);
        $requestParserOptions = [
            'model_namespaces' => [
                'LIQRGV\QueryFilter\Mocks',
            ]
        ];

        $request = $this->createControllerRequest($uri, $controllerClass, $query, $requestParserOptions);

        $requestParser = new RequestParser($request);
        $requestParser->setAllowedFilters(["name", "value"]);
        $builder = $requestParser->getBuilder();

        $query = $builder->getQuery();

        $this->assertEmpty(array_filter($query->wheres, function ($where) use ($ignoredKey) {
            return $where["column"] == $ignoredKey;
        }));

        $nameAndValueColumn = array_filter($query->wheres, function ($where) use ($tableName, $nameKey, $valueKey) {
            return in_array($where["column"], [sprintf("%s.%s", $tableName, $nameKey), sprintf("%s.%s", $tableName, $valueKey)]);
        });
        $this->assertEquals(2, count($nameAndValueColumn));
    }

    function testFilterIgnoreIgnoredFilter()
    {
        $uri = 'some_model';
        $controllerClass = MockModelController::class;
        $tableName = 'mock_models';
        $ignoredKey = "omnisearch";
        $notIgnoredKey = "selected_value";
        $query = new ParameterBag([
            "filter" => [
                $notIgnoredKey => [
                    "is" => "6"
                ],
                $ignoredKey => [
                    "is" => "something"
                ],
            ],
        ]);
        $requestParserOptions = [
            'model_namespaces' => [
                'LIQRGV\QueryFilter\Mocks',
            ]
        ];

        $request = $this->createControllerRequest($uri, $controllerClass, $query, $requestParserOptions);

        $requestParser = new RequestParser($request);
        $requestParser->setIgnoredFilters([$ignoredKey]);
        $builder = $requestParser->getBuilder();

        $query = $builder->getQuery();

        $this->assertEmpty(array_filter($query->wheres, function ($where) use ($tableName, $ignoredKey) {
            return $where["column"] == sprintf("%s.%s", $tableName, $ignoredKey);
        }));
        $this->assertNotEmpty(array_filter($query->wheres, function ($where) use ($tableName, $notIgnoredKey) {
            return $where["column"] == sprintf("%s.%s", $tableName, $notIgnoredKey);
        }));
    }

    function testFilterIgnoredFilterShouldTakePrecedenceOverAllowedFilter()
    {
        $uri = 'some_model';
        $controllerClass = MockModelController::class;
        $tableName = 'mock_models';
        $ignoredKey = "ignored_key";
        $nameKey = "name";
        $valueKey = "value";
        $query = new ParameterBag([
            "filter" => [
                $nameKey => [
                    "in" => "2,3,4"
                ],
                $valueKey => [
                    "is" => "5"
                ],
                $ignoredKey => [
                    "is" => "6"
                ],
            ],
        ]);
        $requestParserOptions = [
            'model_namespaces' => [
                'LIQRGV\QueryFilter\Mocks',
            ]
        ];

        $request = $this->createControllerRequest($uri, $controllerClass, $query, $requestParserOptions);

        $requestParser = new RequestParser($request);
        $requestParser->setIgnoredFilters([$ignoredKey]);
        $requestParser->setAllowedFilters([$nameKey, $valueKey, $ignoredKey]);
        $builder = $requestParser->getBuilder();

        $query = $builder->getQuery();

        $nameAndValueColumn = array_filter($query->wheres, function ($where) use ($tableName, $nameKey, $valueKey) {
            return in_array($where["column"], [sprintf("%s.%s", $tableName, $nameKey), sprintf("%s.%s", $tableName, $valueKey)]);
        });
        $this->assertEquals(2, count($nameAndValueColumn));
        $this->assertEmpty(array_filter($query->wheres, function ($where) use ($ignoredKey) {
            return $where["column"] == $ignoredKey;
        }));
    }
}
