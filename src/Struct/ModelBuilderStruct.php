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

    public function __construct(string $baseModelName, array $filters) {
        $this->baseModelName = $baseModelName;
        $this->filters = $filters;
    }
}
