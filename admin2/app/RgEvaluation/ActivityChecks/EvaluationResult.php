<?php

namespace App\RgEvaluation\ActivityChecks;

class EvaluationResult implements EvaluationResultInterface
{
    private bool $passed = false;

    /**
     * May be used as information source to generate user message
     *
     * @var array
     */
    private array $evaluationVariables = [];

    public function isPassed(): bool
    {
        return $this->passed;
    }

    public function getEvaluationVariables(): array
    {
        return $this->evaluationVariables;
    }

    public function setResult(bool $passed): EvaluationResultInterface
    {
        $this->passed = $passed;

        return $this;
    }

    public function setEvaluationVariables(array $variables): EvaluationResultInterface
    {
        $this->evaluationVariables = $variables;

        return $this;
    }
}