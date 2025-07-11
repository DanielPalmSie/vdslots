<?php

namespace App\RgEvaluation\Triggers;

use App\RgEvaluation\ActivityChecks\ActivityCheckInterface;
use App\RgEvaluation\ActivityChecks\AverageLossPerDay;

class RG80 extends Trigger
{
    public function getActivityCheck(): ActivityCheckInterface
    {
        return new AverageLossPerDay($this->getRgEvaluation()->user, $this);
    }
}
