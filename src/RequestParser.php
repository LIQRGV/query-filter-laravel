<?php

namespace LIQRGV\QueryFilter;

use HaydenPierce\ClassFinder\ClassFinder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Laravel\Lumen\Routing\Controller;
use LIQRGV\QueryFilter\Exception\ModelNotFoundException;
use LIQRGV\QueryFilter\Exception\NotModelException;
use LIQRGV\QueryFilter\Struct\FilterStruct;
use LIQRGV\QueryFilter\Struct\ModelBuilderStruct;
use LIQRGV\QueryFilter\Struct\SortStruct;

class RequestParser
{

    private static $ALLOWED_OPERATOR = [
        "=",
        "!=",
        ">",
        "<",
        "is",
        "!is",
        "in",
        "!in",
        "between",
    ];
    /**
     * @var string
     */
    protected $modelName;
    /**
     * @var bool
     */
    protected $guessModelName = true;
    /**
     * @var array
     */
    private $modelNamespaces;
    /**
     * @var Request
     */
    private $request;

    public function __construct(Request $request)
    {
        $requestParserConfig = Config::get('request_parser');
        if (is_null($requestParserConfig) || empty($requestParserConfig)) {
            $this->modelNamespaces = ["App\\Models"];
        } else {
            $this->modelNamespaces = $requestParserConfig['model_namespaces'];
        }

        $this->request = $request;
    }

    public function setModel(string $modelName): RequestParser
    {
        $this->modelName = $modelName;

        return $this;
    }

    public function getBuilder(): Builder
    {
        $modelBuilderStruct = $this->createModelBuilderStruct($this->request);
        $model = $this->createModel($modelBuilderStruct->baseModelName);

        $builder = $this->applyFilter($model::query(), $modelBuilderStruct->filters);
        $builder = $this->applySorter($builder, $modelBuilderStruct->sorter);
        $builder = $this->applyPaginator($builder, $modelBuilderStruct->paginator);

        return $builder;
    }

    private function createModelBuilderStruct(Request $request): ModelBuilderStruct
    {
        $queryParam = $request->query;
        $filterQuery = $queryParam->get('filter') ?? [];
        $sortQuery = $queryParam->get('sort') ?? null;
        $limitQuery = $queryParam->get('limit') ?? null;
        $offsetQuery = $queryParam->get('offset') ?? 0;

        $baseModelName = $this->getBaseModelName($request);
        $filters = $this->parseFilter($filterQuery);
        $sorter = $this->parseSorter($sortQuery);
        $paginator = $this->parsePaginator($limitQuery, $offsetQuery);

        return new ModelBuilderStruct($baseModelName, $filters, $sorter, $paginator);
    }

    private function getBaseModelName(Request $request): string
    {
        if ($this->modelName) {
            return $this->modelName;
        }

        $errorMessage = 'Model not found, please use "setModel()" method.';
        if ($this->guessModelName) {
            $modelCandidates = [];
            $route = $request->route();
            $controller = $this->getControllerFromRoute($route);
            if ($controller) {
                $stringToRemove = "controller";
                $className = class_basename($controller);
                $maybeBaseModel = substr_replace($className, '', strrpos(strtolower($className), $stringToRemove), strlen($stringToRemove));
                $modelCandidates[] = $maybeBaseModel;

                $modelName = $this->getModelFromNamespaces($maybeBaseModel, $this->modelNamespaces);
                if ($modelName) {
                    return $modelName;
                }
            }

            $exploded = explode("/", $request->getRequesturi());
            $lastURISegment = strtolower(end($exploded));
            $lastURINoQuery = current(explode("?", $lastURISegment, 2));
            $camelizeURI = str_replace('_', '', ucwords($lastURINoQuery, '_'));
            $modelCandidates[] = $camelizeURI;

            $modelName = $this->getModelFromNamespaces($camelizeURI, $this->modelNamespaces);
            if ($modelName) {
                return $modelName;
            }

            $errorMessage = "Model not found after looking on ";
            $searchPath = [];
            foreach ($modelCandidates as $candidate) {
                foreach ($this->modelNamespaces as $modelNamespace) {
                    $searchPath[] = $modelNamespace . '\\' . $candidate;
                }
            }

            $errorMessage .= join(', ', $searchPath);
        }

        throw new ModelNotFoundException($errorMessage);
    }

    private function parseFilter(array $filterQuery = []): array
    {
        $filters = [];

        if (is_array($filterQuery)) {
            foreach ($filterQuery as $key => $operatorValuePairs) {
                if (is_array($operatorValuePairs)) {
                    foreach ($operatorValuePairs as $operator => $value) {
                        if (in_array($operator, static::$ALLOWED_OPERATOR)) {
                            $filters[] = new FilterStruct($key, $operator, $value);
                        }
                    }
                }
            }
        }

        return $filters;
    }

    private function parseSorter(?string $sortQuery): ?array
    {
        if(is_null($sortQuery)) {
            return [];
        }

        $sortStructs = [];

        $fieldPattern = "/^\-?([a-zA-z\_]+)$/";
        $splitedSortQuery = explode(",", $sortQuery);

        foreach ($splitedSortQuery as $singleSortQuery) {
            if(preg_match($fieldPattern, $singleSortQuery, $match)) {
                $fieldName = $match[1];
                $direction = $singleSortQuery[0] == "-" ? "DESC" : "ASC";

                $sortStructs[] = new SortStruct($fieldName, $direction);
            }
        }

        return $sortStructs;
    }

    private function parsePaginator($limitQuery, $offsetQuery){
        return [
            "limit" => $limitQuery,
            "offset" => $offsetQuery
        ];
    }

    private function getModelFromNamespaces(string $modelName, array $modelNamespaces)
    {
        foreach ($modelNamespaces as $modelNamespace) {
            $classes = ClassFinder::getClassesInNamespace($modelNamespace);

            foreach ($classes as $class) {
                if ($class == $modelNamespace . '\\' . $modelName) {
                    return $class;
                }
            }
        }

        return null;
    }

    private function applyFilter(Builder $builder, array $filters): Builder
    {
        foreach ($filters as $filterStruct) {
            $builder = $filterStruct->apply($builder);
        }

        return $builder;
    }

    private function applySorter(Builder $builder, array $sorter)
    {
        if(empty($sorter)) {
            return $builder;
        }

        foreach ($sorter as $sort) {
            $builder = $builder->orderBy($sort->fieldName, $sort->direction);
        }

        return $builder;
    }

    private function applyPaginator(Builder $builder, array $paginator): Builder
    {
        if ($paginator['limit']){
            return $builder->limit($paginator['limit'])->offset($paginator['offset']);
        }
        return $builder;
    }

    private function createModel(string $baseModelName)
    {
        $model = new $baseModelName;
        if (!($model instanceof Model)) {
            throw new NotModelException($baseModelName);
        }

        return $model;
    }

    private function getControllerFromRoute($route)
    {
        if (is_array($route)) {
            $maybeControllerWithMethod = current($route[1]);
            if ($maybeControllerWithMethod instanceof \Closure) {
                return null;
            }

            $splitedControllerMethod = explode('@', $maybeControllerWithMethod);
            $routingHandler = current($splitedControllerMethod);
            $maybeController = new $routingHandler;

            return new $maybeController instanceof Controller ? $maybeController : null;
        }

        return $route->controller;
    }
}
