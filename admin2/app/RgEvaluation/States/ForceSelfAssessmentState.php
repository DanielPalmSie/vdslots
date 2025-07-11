<?php

namespace App\RgEvaluation\States;

use App\Models\UserRgEvaluation;
use App\RgEvaluation\ActivityChecks\EvaluationResultInterface;

/**
 * Transition state between 'started' step and 'self-assessment' step.
 * Means we have completed first step of RG evaluation (started) and move an uses to the next evaluation step.
 * We do nothing in this state except fact of transition to the next level of RG evaluation (self-assessment)
 */
class ForceSelfAssessmentState extends State
{
    public function check(): EvaluationResultInterface
    {
        return $this->getEvaluationResult()->setResult(true);
    }

    protected function onSuccess(): void
    {
        $rgEvaluation = $this->getTrigger()->getRgEvaluation();
        UserRgEvaluation::sh($rgEvaluation->user_id)->create([
            'user_id' => $rgEvaluation->user_id,
            'trigger_name' => $rgEvaluation->trigger_name,
            'step' => UserRgEvaluation::STEP_SELF_ASSESSMENT,
        ]);
        $rgEvaluation->user->repo->setSetting('force_self_assessment_test', 1, false);
    }
}