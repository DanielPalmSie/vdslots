<?php

namespace App\RgEvaluation\States;

use App\Models\Config;
use App\RgEvaluation\ActivityChecks\EvaluationResultInterface;
use Exception;

/**
 * The final generic state.
 * It's a dynamic action that can be changed from Admin panel through RGX-evaluation-last-step-action-state config
 * Default action is TriggerManualReviewState. Or NoActionState in case of no action needed.
 *
 */
class FinalActionState extends State
{
    protected const ACTION_NAME_CONFIG = "-evaluation-last-step-action-state";
    private StateInterface $state;

    public function check(): EvaluationResultInterface
    {
        $trigger = $this->getTrigger();
        $rgEvaluation = $trigger->getRgEvaluation();
        $actionStateName = $this->getActionStateName($rgEvaluation->trigger_name);
        $this->state = StateFactory::create($this->app, $actionStateName)->setTrigger($trigger);

        return $this->state->check();

    }

    protected function onSuccess(): void
    {
        $this->state->onSuccess();
    }

    protected function onFail(): void
    {
        $this->state->onFail();
    }

    /**
     * Returns a dynamic action sub-state name base on db config
     *
     * @param string $triggerName
     *
     * @return string
     */
    protected function getActionStateName(string $triggerName): string
    {
        try {
            return Config::getValue(
                $triggerName . self::ACTION_NAME_CONFIG,
                'RG',
                static::TRIGGER_MANUAL_REVIEW_STATE
            );
        } catch (Exception $exception) {
            return self::NO_ACTION_STATE;
        }
    }
}