<?php

namespace App\RgEvaluation\Triggers;

use App\RgEvaluation\ActivityChecks\ActivityCheckInterface;
use App\RgEvaluation\ActivityChecks\AverageTimeSpentInUniqueGameSessionPerDay;

class RG33 extends Trigger
{
    public function getActivityCheck(): ActivityCheckInterface
    {
        return new AverageTimeSpentInUniqueGameSessionPerDay($this->getRgEvaluation()->user, $this);
    }
}