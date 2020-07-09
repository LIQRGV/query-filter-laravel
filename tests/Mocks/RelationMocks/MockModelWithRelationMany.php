<?php

namespace LIQRGV\QueryFilter\Mocks\RelationMocks;

use Illuminate\Database\Eloquent\Model;
use LIQRGV\QueryFilter\Mocks\MockModel;

class MockModelWithRelationMany extends Model
{
    function mockModels()
    {
        return $this->hasMany(MockModel::class);
    }
}