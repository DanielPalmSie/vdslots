<?php

namespace App\RgEvaluation\States;

use App\Models\UserRgEvaluation;
use App\RgEvaluation\ActivityChecks\EvaluationResult;
use App\RgEvaluation\ActivityChecks\EvaluationResultInterface;
use App\RgEvaluation\States\Comments\StateCommentFactory;
use App\RgEvaluation\States\Comments\StateCommentFactoryInterface;
use App\RgEvaluation\Triggers\TriggerInterface;
use LogicException;
use Silex\Application;

abstract class State implements StateInterface
{
    private ?EvaluationResultInterface $evaluationResult;

    protected ?TriggerInterface $trigger;
    protected Application $app;

    /**
     * Basic general states
     */
    public const CHECK_USERS_GRS_STATE = "CheckUsersGRSState";
    public const CHECK_ACTIVITY_STATE = "CheckActivityState";
    public const FORCE_SELF_ASSESSMENT_STATE = "ForceSelfAssessmentState";
    public const TRIGGER_MANUAL_REVIEW_STATE = "TriggerManualReviewState";

    /**
     * Dynamic action sub-states (manual adjustment by an admin)
     * See DB config RGX-evaluation-last-step-action-state
     */
    public const NO_ACTION_STATE = "NoActionState";
    public const FINAL_ACTION_STATE = "FinalActionState";

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    abstract protected function check(): EvaluationResultInterface;

    /**
     * Do nothing if a check returns positive result. Means a user has reduced activity
     *
     * @return void
     */
    public function execute(): void
    {
        if ($this->check()->isPassed()) {
            $this->onSuccess();
        } else {
            $this->onFail();
            $nextState = $this->trigger->getNextState();
            $this->trigger->transitionTo($nextState);
            $this->trigger->evaluate();
        }
    }

    public function setTrigger(TriggerInterface $trigger): StateInterface
    {
        $this->trigger = $trigger;

        return $this;
    }

    /**
     * @return TriggerInterface
     *
     * @throws LogicException
     */
    public function getTrigger(): TriggerInterface
    {
        if (!$this->isSetTrigger()) {
            throw new LogicException("State " . class_basename($this) . " Trigger not set");
        }
        return $this->trigger;
    }

    public function isSetTrigger(): bool
    {
        return $this->trigger !== null;
    }

    public function getCommentCreator(): StateCommentFactoryInterface
    {
        return new StateCommentFactory;
    }

    /**
     * We have only two evaluation steps now.
     * In case if amount of evaluation steps is growing this part should be extended accordingly
     *
     * @param bool $relative
     *  - true - interval from the last check
     *  - false - interval from the beginning of evaluation process (when a flag was triggered)
     *
     * @return int
     */
    public function currentEvaluationInterval(bool $relative = true): int
    {
        if (!$this->isSetTrigger()) {
            throw new LogicException("State " . class_basename($this) . " Trigger not set");
        }

        if ($this->getTrigger()->getRgEvaluation()->step === UserRgEvaluation::STEP_STARTED) {
            return UserRgEvaluation::FIRST_EVALUATION_INTERVAL_IN_DAYS;
        }

        if ($relative) {
            return UserRgEvaluation::SECOND_EVALUATION_INTERVAL_IN_DAYS;
        }

        return (UserRgEvaluation::FIRST_EVALUATION_INTERVAL_IN_DAYS + UserRgEvaluation::SECOND_EVALUATION_INTERVAL_IN_DAYS);
    }

    /**
     * @inheritDoc
     *
     * We have only two evaluation steps now. Thus, this is static
     * In case if amount of evaluation steps is growing this part should be extended accordingly
     */
    public function nexEvaluationInterval(): int
    {
        return UserRgEvaluation::SECOND_EVALUATION_INTERVAL_IN_DAYS;
    }

    protected function onSuccessComment(array $args): void
    {
        $factory = $this->getCommentCreator();
        $comment = $factory::create($this->app, $this->getTrigger(), StateCommentFactory::ON_SUCCESS);
        $comment->setVariables($args)->addComment($comment->getCommentContext());
    }

    protected function onFailureComment(array $args): void
    {
        $factory = $this->getCommentCreator();
        $comment = $factory::create($this->app, $this->getTrigger(), StateCommentFactory::ON_FAILURE);
        $comment->setVariables($args)->addComment($comment->getCommentContext());
    }

    protected function getEvaluationResult(): EvaluationResultInterface
    {
        if (!isset($this->evaluationResult)) {
            $this->evaluationResult = new EvaluationResult();
        }
        return $this->evaluationResult;
    }

    protected function setEvaluationResult(EvaluationResultInterface $evaluationResult): StateInterface
    {
        $this->evaluationResult = $evaluationResult;
        return $this;
    }

    /**
     * Each subclass may implement the logic if needed.
     * Do nothing by default
     *
     * @return void
     */
    protected function onFail(): void
    {
    }

    /**
     * Each subclass may implement the logic if needed.
     * Do nothing by default
     *
     * @return void
     */
    protected function onSuccess(): void
    {
        $rgEvaluation = $this->getTrigger()->getRgEvaluation();
        $rgEvaluation->update(['processed' => true, 'result' => 0]);
    }
}