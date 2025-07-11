<?php

namespace App\RgEvaluation\Triggers;

use App\RgEvaluation\ActivityChecks\ActivityCheckInterface;
use App\RgEvaluation\ActivityChecks\SpendMoreTimePlayingCheck;

class RG8 extends Trigger
{
    public function getActivityCheck(): ActivityCheckInterface
    {
        return new SpendMoreTimePlayingCheck($this->getRgEvaluation()->user, $this);
    }
}