<?php

namespace App\RgEvaluation\Triggers;

use App\RgEvaluation\ActivityChecks\ActivityCheckInterface;
use App\RgEvaluation\ActivityChecks\AverageTotalDepositedAmountPerDay;

class RG12 extends Trigger
{
    public function getActivityCheck(): ActivityCheckInterface
    {
        return new AverageTotalDepositedAmountPerDay($this->getRgEvaluation()->user, $this);
    }
}