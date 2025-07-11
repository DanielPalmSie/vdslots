<?php

namespace App\RgEvaluation\States;

use App\Models\TriggersLog;
use App\Models\UserRgEvaluation;
use App\Repositories\ActionRepository;
use App\RgEvaluation\ActivityChecks\EvaluationResultInterface;
use Illuminate\Support\Carbon;

/**
 * Mostly used in the end of RG evaluation process.
 * Next action are applied on this state:
 * - end RG evaluation process (by adding final step 'manual-review')
 * - trigger RG69 flag 'Manual Review'
 * - log action with tag 'intervention' about new flag triggering
 */
class TriggerManualReviewState extends State
{
    private const NEXT_STEP = "manual-review";

    protected function check(): EvaluationResultInterface
    {
        return $this->getEvaluationResult()->setResult(true);
    }

    protected function onSuccess(): void
    {
        $rgEvaluation = $this->getTrigger()->getRgEvaluation();
        UserRgEvaluation::sh($rgEvaluation->user_id)->create([
            'user_id' => $rgEvaluation->user_id,
            'trigger_name' => $rgEvaluation->trigger_name,
            'step' => static::NEXT_STEP,
        ]);
        TriggersLog::sh($rgEvaluation->user_id)->create([
            'user_id' => $rgEvaluation->user_id,
            'trigger_name' => 'RG69',
            'created_at' => Carbon::now()->toDateTimeString(),
            'descr' => 'Manual Review'
        ]);
        ActionRepository::logAction(
            $rgEvaluation->user_id,
            "set-flag|mixed - Triggered flag RG69",
            "intervention",
            false,
            null,
            true
        );
    }
}