<?php

namespace App\RgEvaluation\States;

use App\RgEvaluation\ActivityChecks\EvaluationResultInterface;

class NullState extends State
{
    public function check(): EvaluationResultInterface
    {
        return $this->getEvaluationResult()->setResult(true);
    }

    /**
     * Do not change evaluation result to keep relevant result from previous state\
     *
     * @return void
     */
    protected function onSuccess(): void
    {
        $this->getTrigger()->getRgEvaluation()->update(['processed' => true]);
    }
}