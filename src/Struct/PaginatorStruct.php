<?php

namespace LIQRGV\QueryFilter\Struct;

class PaginatorStruct {
    public $limit;
    public $offset;

    public function __construct($limit, $offset) {
        $this->limit = $limit;
        $this->offset = $offset;
    }
}
