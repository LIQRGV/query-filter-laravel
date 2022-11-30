<?php

namespace LIQRGV\QueryFilter\Struct;

class ModelBuilderStruct {

    /**
     * @var string
     */
    public $baseModelName;
    /**
     * @var array
     */
    public $filters;
    /**
     * @var SortStruct
     */
    public $sorter;
    /**
     * @var array
     */
    public $paginator;
    /**
     * @var array
     */
    public $include;

    public function __construct(string $baseModelName) {
        $this->baseModelName = $baseModelName;
    }

    public function setFilters(array $filters)
    {
        $this->filters = $filters;
    }

    public function setSorter(?array $sorter)
    {
        $this->sorter = $sorter;
    }

    public function setPaginator(array $paginator)
    {
        $this->paginator = $paginator;
    }

    public function setInclude($include)
    {
        $this->include = $include;
    }
}
