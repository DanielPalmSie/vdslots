<?php

namespace App\RgEvaluation\Triggers;

use App\RgEvaluation\ActivityChecks\ActivityCheckInterface;
use App\RgEvaluation\ActivityChecks\AverageDepositAmountPerTransactionPerDay;

class RG13 extends Trigger
{
    public function getActivityCheck(): ActivityCheckInterface
    {
        return new AverageDepositAmountPerTransactionPerDay($this->getRgEvaluation()->user, $this);
    }
}