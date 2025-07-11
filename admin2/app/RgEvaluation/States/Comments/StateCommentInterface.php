<?php

namespace App\RgEvaluation\States\Comments;

use App\RgEvaluation\Triggers\TriggerInterface;

interface StateCommentInterface
{
    public function setTrigger(TriggerInterface $trigger): StateCommentInterface;

    public function getTrigger(): TriggerInterface;

    public function getCommentContext(): string;

    public function setVariables(array $variables): StateCommentInterface;

    public function addComment(string $comment): void;
}