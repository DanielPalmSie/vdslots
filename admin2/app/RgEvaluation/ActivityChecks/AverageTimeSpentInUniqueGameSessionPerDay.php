<?php

namespace App\RgEvaluation\ActivityChecks;

class AverageTimeSpentInUniqueGameSessionPerDay extends BaseActivityCheck
{

    public function evaluate(): EvaluationResult
    {
        // TODO: Implement logic.
        $evaluationResult = $this->getEvaluationResult();
        $evaluationResult->setResult(false);
        $evaluationResult->setEvaluationVariables([]);

        return $evaluationResult;
    }
}