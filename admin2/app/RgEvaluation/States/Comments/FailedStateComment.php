<?php

namespace App\RgEvaluation\States\Comments;

class FailedStateComment extends BaseStateComment
{
    protected function getCommentType(): string
    {
        return StateCommentFactory::ON_FAILURE;
    }
}