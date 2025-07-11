<?php

namespace App\RgEvaluation\Triggers;

use App\Models\UserRgEvaluation;
use App\RgEvaluation\ActivityChecks\ActivityCheckInterface;
use App\RgEvaluation\ActivityChecks\SelfLockAgainCheck;
use App\RgEvaluation\States\State;

class RG16 extends Trigger
{
    protected array $stateTransitionMap = [
        UserRgEvaluation::STEP_STARTED => [
            State::CHECK_USERS_GRS_STATE => State::CHECK_ACTIVITY_STATE,
            State::CHECK_ACTIVITY_STATE => State::FINAL_ACTION_STATE,
        ],
    ];

    public function getActivityCheck(): ActivityCheckInterface
    {
        return new SelfLockAgainCheck($this->getRgEvaluation()->user, $this);
    }
}
