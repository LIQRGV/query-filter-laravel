<?php

namespace LIQRGV\QueryFilter\Struct;

class FilterStruct {
    private static $OPERATOR_MAPPING = [
        "is" => "=",
        "!is" => "!=",
    ];

    private static $WHERE_QUERY_MAPPING = [
        "in" => "whereIn",
        "!in" => "whereNotIn",
        "between" => "whereBetween",
    ];

    public $fieldName;
    public $operator;
    public $value;

    public function __construct($fieldName, $operator, $value) {
        $this->fieldName = $fieldName;
        $this->operator = $this->transformOperator($operator);
        $this->value = $value;
    }

    public function apply($object) {
        if(array_key_exists($this->operator, self::$WHERE_QUERY_MAPPING)) {
            $whereQuery = self::$WHERE_QUERY_MAPPING[$this->operator];

            return $object->$whereQuery($this->fieldName, $this->value);
        }

        return $object->where($this->fieldName, $this->operator, $this->value);
    }

    private function transformOperator($rawOperator) {
        if(array_key_exists($rawOperator, self::$OPERATOR_MAPPING)) {
            return self::$OPERATOR_MAPPING[$rawOperator];
        }

        return $rawOperator;
    }
}
