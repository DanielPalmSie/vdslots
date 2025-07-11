<?php

namespace App\RgEvaluation\ActivityChecks;

use App\Models\RgLimits;

class NotSetAnyLimit extends BaseActivityCheck
{
    public function evaluate(): EvaluationResult
    {
        /**
         * Currently only those limits are taking into account.
         * See \Rg::onRgLimitChange() on the Phive
         */
        $limit_types = ['loss', 'loss-sportsbook'];
        $state = $this->getTrigger()->getCurrentState();
        $evaluationInterval = $state->currentEvaluationInterval();
        $usersRgLimits = RgLimits::where('user_id', $this->getUser()->id)
            ->whereIn('type', $limit_types)
            ->get()
            ->toArray();
        $evaluationResult = $this->getEvaluationResult();
        $evaluationResult->setResult(!empty($usersRgLimits));
        $evaluationResult->setEvaluationVariables([
            'evaluation_interval' => $evaluationInterval,
            'nex_evaluation_in_days' => $state->nexEvaluationInterval(),
        ]);

        return $evaluationResult;
    }
}