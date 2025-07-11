<?php


namespace App\Repositories;

use App\Models\Operator;

class OperatorRepository
{
    public function getOperatorList()
    {
        return Operator::select('name', 'network')->distinct()->orderBy('name')->get();
    }
}
