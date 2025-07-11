<?php

namespace App\RgEvaluation\Triggers;

use App\Models\UserRgEvaluation;
use App\RgEvaluation\ActivityChecks\ActivityCheckInterface;
use App\RgEvaluation\States\StateInterface;

interface TriggerInterface
{
    public function transitionTo(StateInterface $state): void;

    public function evaluate(): void;

    public function getNextState(): StateInterface;

    public function getCurrentState(): StateInterface;

    public function getActivityCheck(): ActivityCheckInterface;

    public function getRgEvaluation(): UserRgEvaluation;
}