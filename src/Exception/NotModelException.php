<?php


namespace LIQRGV\QueryFilter\Exception;


class NotModelException extends \Exception
{

    /**
     * NotModelException constructor.
     * @param $baseModelName
     */
    public function __construct($baseModelName)
    {
        parent::__construct($baseModelName . " not instance of Illuminate\Database\Eloquent\Model");
    }
}