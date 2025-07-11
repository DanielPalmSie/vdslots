<?php

namespace RgAction\Actions;

use Carbon\Carbon;
use DBUser;

abstract class Action implements ActionInterface
{
    /**
     * Actions
     */
    public const MANUAL_REVIEW_ACTION = 'TriggerManualReviewAction';
    public const NO_ACTION = 'NoAction';

    /**
     * Properties
     */
    protected bool $enabled = false;
    protected DBUser $user;
    protected string $trigger_name;
    /**
     * This property may be overridden in subclasses in a case of custom message is needed
     * @var string
     */
    protected string $action_log_message = "Customer triggered {{trigger_name}} {{trigger_count_threshold}} time(s)" .
    " within a {{period_in_days}}-day(s) period. Action taken: {{action}}";
    protected string $user_comment_message = "Customer triggered {{trigger_name}} {{trigger_count_threshold}} time(s)" .
    " within a {{period_in_days}}-day(s) period. Action taken: {{action}}";
    private int $period_in_days;
    private int $trigger_count_threshold;

    public function __construct(DBUser $user, string $trigger_name)
    {
        $this->user = $user;
        $this->trigger_name = $trigger_name;

        $this->period_in_days = (int) phive('Config')->getValue('RG', "{$trigger_name}-trigger-action-days", 0);
        $this->trigger_count_threshold = (int) phive('Config')->getValue('RG', "{$trigger_name}-trigger-action-count", 0);
    }

    public function perform(): void
    {
        if (!$this->shouldAct($this->trigger_name)) {
            return;
        }

        $this->execute();
        $this->logAction();
        $this->addUserComment();
    }

    /**
     * Checks if action is allowed to be executed or not based on several conditions:
     * - nonempty config RGX-trigger-action-days
     * - nonempty config RGX-trigger-action-count
     * - action is predefined in config RGX-trigger-action and action is enabled
     *
     * @param string $trigger_name
     *
     * @return bool
     */
    private function shouldAct(string $trigger_name): bool
    {
        $user_id = $this->user->getId();
        $date = Carbon::now()->subDays($this->period_in_days)->toDateString();

        if (!$this->isEnabledAction() || empty($this->period_in_days) || empty($this->trigger_count_threshold)) {
            return false;
        }

        $result = phive('SQL')
            ->sh($user_id)
            ->getValue("
                SELECT count(*) as `total` FROM triggers_log WHERE user_id = {$user_id}
                    AND trigger_name = '{$trigger_name}'
                    AND date(created_at) >= '{$date}'
                    GROUP BY trigger_name;"
            );

        return $result['total'] >= $this->trigger_count_threshold;
    }

    /**
     * Each Action subclass has 'enabled' property that indicate is this action switched on/off
     *
     * @return bool
     */
    private function isEnabledAction(): bool
    {
        return $this->enabled;
    }

    private function logAction(): void
    {
        phive('UserHandler')->logAction(
            $this->user->getId(),
            $this->buildMessage($this->action_log_message, $this->getActionMessageVariables()),
            'intervention'
        );
    }

    /**
     * @return void
     */
    private function addUserComment(): void
    {
        $this->user->addComment(
            $this->buildMessage($this->user_comment_message, $this->getUserCommentVariables()),
            0,
            'rg-action'
        );
    }

    /**
     * This function may be overridden in subclasses in case of custom variables set needed
     *
     * @return array
     */
    protected function getActionMessageVariables(): array
    {
        return [
            'trigger_name' => $this->trigger_name,
            'period_in_days' => $this->period_in_days,
            'trigger_count_threshold' => $this->trigger_count_threshold,
            'action' => $this->getActionTitle(),
        ];
    }

    /**
     * This function may be overridden in subclasses in case of custom variables set needed
     *
     * @return array
     */
    protected function getUserCommentVariables(): array
    {
        return [
            'trigger_name' => $this->trigger_name,
            'period_in_days' => $this->period_in_days,
            'trigger_count_threshold' => $this->trigger_count_threshold,
            'action' => $this->getActionTitle(),
        ];
    }

    /**
     * @param string $message
     * @param array  $variables
     *
     * @return string
     */
    protected function buildMessage(string $message, array $variables): string
    {
        return phive('Localizer')->doReplace($message, $variables);
    }

    /**
     * Executes the main action logic
     *
     * @return void
     */
    abstract protected function execute(): void;

    abstract protected function getActionTitle(): string;
}