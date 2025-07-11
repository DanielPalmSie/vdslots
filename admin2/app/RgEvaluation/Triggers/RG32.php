<?php

namespace App\RgEvaluation\Triggers;

use App\RgEvaluation\ActivityChecks\ActivityCheckInterface;
use App\RgEvaluation\ActivityChecks\AverageSessionTimePerDay;

class RG32 extends Trigger
{
    public function getActivityCheck(): ActivityCheckInterface
    {
        return new AverageSessionTimePerDay($this->getRgEvaluation()->user, $this);
    }
}