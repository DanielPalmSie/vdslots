<?php

namespace App\RgEvaluation\Triggers;

use App\RgEvaluation\ActivityChecks\ActivityCheckInterface;
use App\RgEvaluation\ActivityChecks\AverageUniqueGameSessionPerDay;

class RG34 extends Trigger
{
    public function getActivityCheck(): ActivityCheckInterface
    {
        return new AverageUniqueGameSessionPerDay($this->getRgEvaluation()->user, $this);
    }
}