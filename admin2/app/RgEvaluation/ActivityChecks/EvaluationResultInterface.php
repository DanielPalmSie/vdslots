<?php

namespace App\RgEvaluation\ActivityChecks;

interface EvaluationResultInterface
{
    public function isPassed(): bool;
    public function getEvaluationVariables(): array;

    public function setResult(bool $passed): EvaluationResultInterface;

    public function setEvaluationVariables(array $variables): EvaluationResultInterface;
}