<?php

namespace App\RgEvaluation\States;

/**
 * This is predefined blank state for an Admin for cases when no actions required for an abstract ActionState
 * An abstract ActionState means any action state for any step.
 * Example: dynamic FinalActionState can trigger TriggerManualReviewState OR NoActionState
 * The exact state is defined in DB config 'RGX-evaluation-last-step-action-state'
 *
 */
class NoActionState extends NullState
{
}