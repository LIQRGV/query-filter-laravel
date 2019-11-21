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
        if (strpos($fieldName, self::$LOGICAL_OR)) {
            $this->fieldName = explode(self::$LOGICAL_OR, $fieldName);
        } else {
            $this->fieldName = $fieldName;
        }
        $this->operator = $this->transformOperator($operator);
        $this->value = $value;
    }

    public function apply($object) {
        if (is_array($this->fieldName)) {
            $me = $this;

            $subquery = function ($query) use ($me) {
                $query = $query->where($me->fieldName[0], $me->operator, $me->value);

                for ($fieldIndex = 1; $fieldIndex < count($me->fieldName); $fieldIndex++) {
                    $query = $query->orWhere($me->fieldName[$fieldIndex], $me->operator, $me->value);
                }

                return $query;
            };

            return $object->where($subquery);
        }

        if(array_key_exists($this->operator, self::$WHERE_QUERY_MAPPING)) {
            $whereQuery = self::$WHERE_QUERY_MAPPING[$this->operator];

            if (is_string($this->value)) {
                $this->value = explode(",", $this->value);
            }

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
