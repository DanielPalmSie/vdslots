<?php

namespace App\RgEvaluation\Triggers;

use App\RgEvaluation\ActivityChecks\ActivityCheckInterface;
use App\RgEvaluation\ActivityChecks\AverageTotalBetsDuringTheNightTime;

class RG59 extends Trigger
{
    public function getActivityCheck(): ActivityCheckInterface
    {
        return new AverageTotalBetsDuringTheNightTime($this->getRgEvaluation()->user, $this);
    }
}