<?php

namespace RgAction\Actions;

use DBUser;

class ActionBuilder
{
    /**
     * @param DBUser $user
     * @param string $trigger_name
     *
     * @return ActionInterface
     */
    public static function build(DBUser $user, string $trigger_name): ActionInterface
    {
        $action_name = phive('Config')->getValue('RG', "{$trigger_name}-trigger-action", Action::NO_ACTION);
        switch ($action_name) {
            case Action::MANUAL_REVIEW_ACTION:
                return new TriggerManualReviewAction($user, $trigger_name);
            default:
                return new NoAction($user, $trigger_name);
        }
    }
}