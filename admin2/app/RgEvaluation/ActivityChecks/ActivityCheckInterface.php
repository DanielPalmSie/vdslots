<?php

namespace App\RgEvaluation\ActivityChecks;

use App\Models\User;
use App\RgEvaluation\Triggers\TriggerInterface;

interface ActivityCheckInterface
{
    public function evaluate(): EvaluationResult;

    public function getUser(): User;

    public function getTrigger(): TriggerInterface;

    public function getEvaluationResult(): EvaluationResultInterface;

    public function getChangeInPercentage($current, $previous);
}