<?php

namespace App\RgEvaluation\States;

use App\RgEvaluation\States\Comments\StateCommentFactoryInterface;
use App\RgEvaluation\Triggers\TriggerInterface;

interface StateInterface
{
    public function execute(): void;

    public function getCommentCreator(): StateCommentFactoryInterface;

    public function setTrigger(TriggerInterface $trigger): StateInterface;

    public function getTrigger(): TriggerInterface;

    /**
     * Returns interval of evaluation (in days) for the current evaluation step
     *
     * @param bool $relative
     *  - true - interval from the last check
     *  - false - interval from the beginning of evaluation process (when a flag was triggered)
     *
     *
     * @return int
     * @throws \LogicException
     */
    public function currentEvaluationInterval(bool $relative = true): int;

    /**
     * Returns interval of evaluation (in days) for the next evaluation step
     *
     * @return int
     */
    public function nexEvaluationInterval(): int;
}