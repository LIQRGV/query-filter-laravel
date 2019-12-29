<?php

namespace Tests\LIQRGV\QueryFilter;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Config;
use LIQRGV\QueryFilter\Exception\ModelNotFoundException;
use LIQRGV\QueryFilter\Exception\NotModelException;
use LIQRGV\QueryFilter\Mocks\MockModelController;
use LIQRGV\QueryFilter\RequestParser;
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

    public function testModelSetter()
    {
        $this->markTestSkipped('Test for debug purpose only, change createModelBuilderStruct modifier to public to use');
        $uri = 'non_exists_model';
        $query = new ParameterBag([
            "filter" => [
                "y" => [
                    "in" => "2,3,4",
                ],
            ],
        ]);
        $requestParserOptions = [];

        $request = $this->createClosureRequest($uri, $query, $requestParserOptions);

        $requestParser = new RequestParser($request);
        $requestParser->setModel('LIQRGV\QueryFilter\Mocks\MockModel');
        $builderStruct = $requestParser->createModelBuilderStruct($request);
        $this->assertEquals('LIQRGV\QueryFilter\Mocks\MockModel', $builderStruct->baseModelName);
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
        $builder = $requestParser->guessModelName()->getBuilder();

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
        $builder = $requestParser->guessModelName()->getBuilder();

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
        $builder = $requestParser->guessModelName()->getBuilder();

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
        $builder = $requestParser->guessModelName()->getBuilder();

        $query = $builder->getQuery();
        $this->assertEquals("mock_models", $query->from);
        $this->assertEquals("y", $query->wheres[0]['column']);
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
        $requestParser->guessModelName()->getBuilder();
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
        $builder = $requestParser->guessModelName()->getBuilder();

        $query = $builder->getQuery();

        $this->assertEquals("mock_models", $query->from);

        //Assert subquery exists
        $this->assertArrayHasKey('query', $query->wheres[0]);
        $subquery = $query->wheres[0]['query'];

        //Assert subquery components
        $this->assertEquals("x", $subquery->wheres[0]['column']);
        $this->assertEquals("=", strtolower($subquery->wheres[0]['operator']));

        $this->assertEquals("y", $subquery->wheres[1]['column']);
        $this->assertEquals("=", strtolower($subquery->wheres[1]['operator']));
        $this->assertEquals("or", strtolower($subquery->wheres[1]['boolean']));

        //Assert second where term of the main query
        $this->assertEquals("z", $query->wheres[1]['column']);
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
        $builder = $requestParser->guessModelName()->getBuilder();

        $query = $builder->getQuery();

        $this->assertEquals("mock_models", $query->from);

        $this->assertArrayHasKey('query', $query->wheres[0]);
        $this->assertEquals('Nested', $query->wheres[0]['type']);

        $subquery1 = $query->wheres[0]['query'];

        $this->assertEquals("c", $subquery1->wheres[0]['column']);
        $this->assertEquals("in", strtolower($subquery1->wheres[0]['type']));

        $this->assertEquals("d", $subquery1->wheres[1]['column']);
        $this->assertEquals("in", strtolower($subquery1->wheres[1]['type']));
        $this->assertEquals("or", strtolower($subquery1->wheres[1]['boolean']));

        $this->assertEquals("i", $query->wheres[1]['column']);
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
        $builder = $requestParser->guessModelName()->getBuilder();

        $query = $builder->getQuery();

        $this->assertEquals("mock_models", $query->from);

        $this->assertArrayHasKey('query', $query->wheres[0]);
        $this->assertEquals('Nested', $query->wheres[0]['type']);

        $subquery1 = $query->wheres[0]['query'];

        $this->assertEquals("e", $subquery1->wheres[0]['column']);
        $this->assertEquals("NotIn", $subquery1->wheres[0]['type']);

        $this->assertEquals("f", $subquery1->wheres[1]['column']);
        $this->assertEquals("NotIn", $subquery1->wheres[1]['type']);
        $this->assertEquals("or", strtolower($subquery1->wheres[1]['boolean']));

        $this->assertEquals("i", $query->wheres[1]['column']);
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
        $builder = $requestParser->guessModelName()->getBuilder();

        $query = $builder->getQuery();

        $this->assertEquals("mock_models", $query->from);

        $this->assertArrayHasKey('query', $query->wheres[0]);
        $this->assertEquals('Nested', $query->wheres[0]['type']);

        $subquery1 = $query->wheres[0]['query'];

        $this->assertEquals("g", $subquery1->wheres[0]['column']);
        $this->assertEquals("between", strtolower($subquery1->wheres[0]['type']));

        $this->assertEquals("h", $subquery1->wheres[1]['column']);
        $this->assertEquals("between", strtolower($subquery1->wheres[1]['type']));
        $this->assertEquals("or", strtolower($subquery1->wheres[1]['boolean']));

        $this->assertEquals("i", $query->wheres[1]['column']);
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
        $builder = $requestParser->guessModelName()->getBuilder();

        $query = $builder->getQuery();
        $this->assertEquals("mock_models", $query->from);
        $this->assertEquals(100, $query->limit);
        $this->assertEquals(50, $query->offset);
    }

    function testPaginationIgnoreOffset(){
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
        $builder = $requestParser->guessModelName()->getBuilder();

        $query = $builder->getQuery();
        $this->assertEquals("mock_models", $query->from);
        $this->assertNull($query->limit);
        $this->assertNull($query->offset);
    }

    function testPaginationUsingZeroAsDefaultOffset(){
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
        $builder = $requestParser->guessModelName()->getBuilder();

        $query = $builder->getQuery();
        $this->assertEquals("mock_models", $query->from);
        $this->assertEquals(50, $query->limit);
        $this->assertEquals(0, $query->offset);
    }
}
