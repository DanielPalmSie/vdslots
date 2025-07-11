<?php

namespace App\RgEvaluation\Triggers;

use App\Models\UserRgEvaluation;
use App\RgEvaluation\ActivityChecks\ActivityCheckInterface;
use App\RgEvaluation\States\NullState;
use App\RgEvaluation\States\State;
use App\RgEvaluation\States\StateFactory;
use App\RgEvaluation\States\StateInterface;
use Silex\Application;

abstract class Trigger implements TriggerInterface
{
    protected StateInterface $state;
    protected UserRgEvaluation $rgEvaluation;
    private Application $app;

    abstract public function getActivityCheck(): ActivityCheckInterface;

    /**
     * Base flow for most common use. Can be override in subclasses
     *
     * @var array|array[]
     */
    protected array $stateTransitionMap = [
        UserRgEvaluation::STEP_STARTED => [
            State::CHECK_USERS_GRS_STATE => State::CHECK_ACTIVITY_STATE,
            State::CHECK_ACTIVITY_STATE => State::FORCE_SELF_ASSESSMENT_STATE,
        ],
        UserRgEvaluation::STEP_SELF_ASSESSMENT => [
            State::CHECK_USERS_GRS_STATE => State::CHECK_ACTIVITY_STATE,
            State::CHECK_ACTIVITY_STATE => State::FINAL_ACTION_STATE,
        ],
    ];

    public function __construct(UserRgEvaluation $rgEvaluation, State $state, Application $app)
    {
        $this->rgEvaluation = $rgEvaluation;
        $this->app = $app;
        $this->transitionTo($state);
    }

    public function transitionTo(StateInterface $state): void
    {
        $this->state = $state;
        $this->state->setTrigger($this);
    }

    public function evaluate(): void
    {
        $this->state->execute();
    }

    public function getNextState(): StateInterface
    {
        $stateName = $this->stateTransitionMap[$this->rgEvaluation->step][class_basename($this->state)] ?? null;

        if ($stateName === null) {
            return new NullState($this->app);
        }

        return StateFactory::create($this->app, $stateName);
    }

    public function getRgEvaluation(): UserRgEvaluation
    {
        return $this->rgEvaluation;
    }

    public function getCurrentState(): StateInterface
    {
        return $this->state;
    }
}