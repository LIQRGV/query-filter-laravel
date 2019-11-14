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

    public function __construct(string $baseModelName, array $filters, ?SortStruct $sorter) {
        $this->baseModelName = $baseModelName;
        $this->filters = $filters;
        $this->sorter = $sorter;
    }
}
