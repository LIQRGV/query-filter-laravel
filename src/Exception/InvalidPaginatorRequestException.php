<?php


namespace LIQRGV\QueryFilter\Exception;


class InvalidPaginatorRequestException extends \Exception
{

    public function __construct()
    {
        parent::__construct('Both limit & offset must exist');
    }
}