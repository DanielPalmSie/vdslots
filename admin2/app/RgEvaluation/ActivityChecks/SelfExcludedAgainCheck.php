<?php

namespace App\RgEvaluation\ActivityChecks;

use App\Repositories\BlockRepository;

class SelfExcludedAgainCheck extends BaseActivityCheck
{
    public function evaluate(): EvaluationResult
    {
        $blockRepo = new BlockRepository($this->getUser());
        $blockRepo->populateSettings();
        $evaluationResult = $this->getEvaluationResult();
        $evaluationResult->setResult(!$blockRepo->isSelfExcluded() && !$blockRepo->isExternalSelfExcluded());
        $evaluationResult->setEvaluationVariables([
            'evaluation_interval' => $this->getTrigger()->getCurrentState()->currentEvaluationInterval()
        ]);

        return $evaluationResult;
    }
}