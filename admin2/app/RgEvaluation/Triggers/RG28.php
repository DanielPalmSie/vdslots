<?php

namespace App\RgEvaluation\Triggers;

use App\RgEvaluation\ActivityChecks\ActivityCheckInterface;
use App\RgEvaluation\ActivityChecks\AverageTotalBetsPerDay;

class RG28 extends Trigger
{
    public function getActivityCheck(): ActivityCheckInterface
    {
        return new AverageTotalBetsPerDay($this->getRgEvaluation()->user, $this);
    }
}