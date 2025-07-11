<?php

namespace App\RgEvaluation\Triggers;

use App\RgEvaluation\ActivityChecks\ActivityCheckInterface;
use App\RgEvaluation\ActivityChecks\NotSetAnyLimit;

class RG21 extends Trigger
{
    public function getActivityCheck(): ActivityCheckInterface
    {
        return new NotSetAnyLimit($this->getRgEvaluation()->user, $this);
    }
}