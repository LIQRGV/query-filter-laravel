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
     * @var PaginatorStruct
     */
    public $paginator;

    public function __construct(string $baseModelName, array $filters, array $sorter, array $paginator) {
        $this->baseModelName = $baseModelName;
        $this->filters = $filters;
        $this->sorter = $sorter;
        $this->paginator = $paginator;
    }
}
