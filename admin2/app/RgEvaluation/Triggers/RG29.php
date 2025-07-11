<?php

namespace App\RgEvaluation\Triggers;

use App\RgEvaluation\ActivityChecks\ActivityCheckInterface;
use App\RgEvaluation\ActivityChecks\AverageTotalOfUniqueBetTransactionPerDay;

class RG29 extends Trigger
{
    public function getActivityCheck(): ActivityCheckInterface
    {
        return new AverageTotalOfUniqueBetTransactionPerDay($this->getRgEvaluation()->user, $this);
    }
}