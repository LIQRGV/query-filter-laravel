<?php

namespace LIQRGV\QueryFilter\Struct;

class SortStruct {
    public $fieldName;
    public $direction;

    public function __construct($fieldName, $direction) {
        $this->fieldName = $fieldName;
        $this->direction = $direction;
    }
}
