<?php

namespace RgAction\Actions;

class NoAction extends Action
{
    protected function execute(): void
    {
        // do nothing
    }

    protected function getActionTitle(): string
    {
        return "Do nothing";
    }
}