<?php

namespace App\RgEvaluation\ActivityChecks;

use App\Models\User;
use App\RgEvaluation\Triggers\TriggerInterface;

abstract class BaseActivityCheck implements ActivityCheckInterface
{
    protected int $activitiesIntervalInDaysBeforeEvaluationStarted = 30;

    private ?EvaluationResultInterface $evaluationResult;
    private User $user;
    private TriggerInterface $trigger;

    public function __construct(User $user, TriggerInterface $trigger)
    {
        $this->user = $user;
        $this->trigger = $trigger;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @return TriggerInterface
     */
    public function getTrigger(): TriggerInterface
    {
        return $this->trigger;
    }

    public function getEvaluationResult(): EvaluationResultInterface
    {
        if (!isset($this->evaluationResult)) {
            $this->evaluationResult = new EvaluationResult();
        }
        return $this->evaluationResult;
    }


    /**
     * @param float|int $current
     * @param float|int $previous
     *
     * @return float|int
     */
    public function getChangeInPercentage($current, $previous)
    {
        if ($previous === 0) {
            return 0;
        }

        return (($current / $previous) - 1) * 100; // -1 (so we end up with 20% instead of 120% if $current is 120 and $previous is 100)
    }

    abstract public function evaluate(): EvaluationResult;
}