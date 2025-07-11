<?php

use RgAction\Actions\ActionBuilder;

class RgTriggerActionPerformer
{
    private DBUser $user;

    public function executeAction(DBUser $user, string $trigger_name): void
    {
        $this->user = $user;

        if (stripos($trigger_name, 'RG') === false) {
            return;
        }

        ActionBuilder::build($user, $trigger_name)->perform();
    }
}