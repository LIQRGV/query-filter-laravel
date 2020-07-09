<?php

namespace LIQRGV\QueryFilter\Mocks\RelationMocks;

use Illuminate\Database\Eloquent\Model;
use LIQRGV\QueryFilter\Mocks\MockModel;

class MockModelWithRelationOne extends Model
{
    function mockModel()
    {
        return $this->hasOne(MockModel::class);
    }
}