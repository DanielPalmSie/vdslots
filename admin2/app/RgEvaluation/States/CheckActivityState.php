<?php

namespace App\RgEvaluation\States;

use App\RgEvaluation\ActivityChecks\EvaluationResultInterface;

/**
 * One of the middle states of RG evaluation processes.
 * Checks user's activities based on specific flag conditions
 * Adds a user profile comment about successful or failure result of check
 * Completes the RG evaluation if the user has reduced negative activity
 */
class CheckActivityState extends State
{
    protected function check(): EvaluationResultInterface
    {
        $result = $this->getTrigger()->getActivityCheck()->evaluate();
        $this->setEvaluationResult($result);

        return $result;
    }

    protected function onSuccess(): void
    {
        parent::onSuccess();
        $this->onSuccessComment(
            $this->getEvaluationResult()->getEvaluationVariables()
        );
    }

    protected function onFail(): void
    {
        $rgEvaluation = $this->getTrigger()->getRgEvaluation();
        $rgEvaluation->update(['processed' => true, 'result' => 1]);
        $this->onFailureComment(
            $this->getEvaluationResult()->getEvaluationVariables()
        );
    }
}