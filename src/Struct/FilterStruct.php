<?php

namespace LIQRGV\QueryFilter\Struct;

class FilterStruct {
    private static $OPERATOR_MAPPING = [
        "is" => "=",
        "!is" => "!=",
    ];

    private static $LOGICAL_OR = "|";

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
        if (strpos($this->fieldName, self::$LOGICAL_OR)) {
            $fieldNames = explode(self::$LOGICAL_OR, $this->fieldName);
            $fieldNameCount = count($fieldNames);
            $subquery = function ($query) use ($fieldNames, $fieldNameCount) {
                for ($fieldIndex = 0; $fieldIndex < $fieldNameCount; $fieldIndex++) {
                    $query = $this->_apply($query, $fieldNames[$fieldIndex], 'or');
                }
                return $query;
            };
            return $object->where($subquery);
        }

        return $this->_apply($object, $this->fieldName, 'and');
    }

    private function _apply($object, $fieldName, $boolean) {
        if (array_key_exists($this->operator, self::$WHERE_QUERY_MAPPING)) {
            $whereQuery = self::$WHERE_QUERY_MAPPING[$this->operator];
            $value = explode(",", $this->value);

            return $object->$whereQuery($fieldName, $value, $boolean);
        }

        return $object->where($fieldName, $this->operator, $this->value, $boolean);
    }

    private function transformOperator($rawOperator) {
        if (array_key_exists($rawOperator, self::$OPERATOR_MAPPING)) {
            return self::$OPERATOR_MAPPING[$rawOperator];
        }

        return $rawOperator;
    }
}
