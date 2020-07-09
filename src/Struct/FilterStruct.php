<?php

namespace LIQRGV\QueryFilter\Struct;

use Illuminate\Database\Eloquent\Builder;

class FilterStruct {
    private static $OPERATOR_MAPPING = [
        "is" => "=",
        "!is" => "!=",
    ];

    private static $LOGICAL_OR_FLAG = "|";
    private static $NOT_FLAG = "!";

    private static $WHERE_QUERY_MAPPING = [
        "in" => "whereIn",
        "!in" => "whereNotIn",
        "between" => "whereBetween",
    ];

    private static $RELATION_FLAG_MAPPING = [
        true => "whereHas",
        false => "whereDoesntHave",
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
        if (strpos($this->fieldName, self::$LOGICAL_OR_FLAG)) {
            $fieldNames = explode(self::$LOGICAL_OR_FLAG, $this->fieldName);
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

    private function _apply(Builder $object, $fieldName, $boolean, $isChild = false) {
        if ($this->isRelation($fieldName)) {
            return $this->_applyRelation($object, $fieldName, $boolean, $isChild);
        }
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

    private function _applyRelation(Builder $object, $fieldName, $boolean, $isChild)
    {
        [$relation, $other] = explode('.', $fieldName, 2);

        $relationQuery = "whereHas";
        if (!$isChild) {
            $operatorType = $this->operator[0] !== self::$NOT_FLAG;
            $relationQuery = self::$RELATION_FLAG_MAPPING[$operatorType];
        }

        if ($boolean === 'or') {
            $relationQuery = "or" . ucfirst($relationQuery);
        }

        return $object->$relationQuery($relation, function (Builder $relation) use ($other) {
            return $this->_apply($relation, $other, 'and');
        });
    }

    private function isRelation($fieldName)
    {
        return strpos($fieldName, '.') !== false;
    }
}
