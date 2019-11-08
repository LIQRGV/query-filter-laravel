<?php

namespace LIQRGV\QueryFilter;

use HaydenPierce\ClassFinder\ClassFinder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use LIQRGV\QueryFilter\Exception\ModelNotFoundException;
use LIQRGV\QueryFilter\Exception\NotModelException;
use LIQRGV\QueryFilter\Struct\FilterStruct;
use LIQRGV\QueryFilter\Struct\ModelBuilderStruct;

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
     * @var array
     */
    private $modelNamespaces;

    public function __construct(array $config)
    {
        $this->modelNamespaces = $config['model_namespaces'] ?? ["App\\Models"];
    }

    public function parse(Request $request)
    {
        $modelBuilderStruct = $this->createModelBuilderStruct($request);
        $model = $this->createModel($modelBuilderStruct->baseModelName);

        return $this->applyFilter($model::query(), $modelBuilderStruct->filters);
    }

    public function createModelBuilderStruct(Request $request): ModelBuilderStruct
    {
        $requestRoute = $request->route();
        $filterQuery = $request->filter ?: [];
        $isMandatory = $this->isBaseModelMandatory($filterQuery);

        $baseModelName = $this->getBaseModelName($requestRoute, $isMandatory);
        $filters = $this->parseFilter($filterQuery);

        return new ModelBuilderStruct($baseModelName, $filters);
    }

    private function getBaseModelName(Route $route, bool $isMandatory): string
    {
        $modelCandidates = [];
        if ($route->controller) {
            $stringToRemove = "controller";
            $className = class_basename($route->controller);
            $maybeBaseModel = substr_replace($className, '', strrpos(strtolower($className), $stringToRemove), strlen($stringToRemove));
            $modelCandidates[] = $maybeBaseModel;

            $modelName = $this->getModelFromNamespaces($maybeBaseModel, $this->modelNamespaces);
            if ($modelName) {
                return $modelName;
            }
        }

        $exploded = explode("/", $route->uri);
        $lastURISegment = strtolower(end($exploded));
        $camelizeURI = str_replace('_', '', ucwords($lastURISegment, '_'));
        $modelCandidates[] = $camelizeURI;

        $modelName = $this->getModelFromNamespaces($camelizeURI, $this->modelNamespaces);
        if ($modelName) {
            return $modelName;
        }

        if($isMandatory) {
            $errorMessage = "Model not found after looking on ";
            $searchPath = [];
            foreach ($modelCandidates as $candidate) {
                foreach ($this->modelNamespaces as $modelNamespace) {
                    $searchPath[] = $modelNamespace . '\\' . $candidate;
                }
            }

            $errorMessage .= join(', ', $searchPath);

            throw new ModelNotFoundException($errorMessage);
        }
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

    private function isBaseModelMandatory(array $filterQuery)
    {
        foreach ($filterQuery as $key => $value) {
            if (strpos($key, ".") !== false) {
                return true;
            }
        }

        return false;
    }

    public function createModel(string $baseModelName)
    {
        $model = new $baseModelName;
        if (!($model instanceof Model)) {
            throw new NotModelException($baseModelName);
        }

        return $model;
    }
}
