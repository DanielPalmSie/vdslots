<?php

namespace RgAction\Actions;

class TriggerManualReviewAction extends Action
{
    protected bool $enabled = true;

    protected function execute(): void
    {
        phive('UserHandler')->logTrigger($this->user, "RG69", "Manual review");
    }

    protected function getActionTitle(): string
    {
        return "Manual Flag";
    }
}