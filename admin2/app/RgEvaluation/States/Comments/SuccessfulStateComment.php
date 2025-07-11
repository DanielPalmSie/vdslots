<?php

namespace App\RgEvaluation\States\Comments;

class SuccessfulStateComment extends BaseStateComment
{
    protected function getCommentType(): string
    {
        return StateCommentFactory::ON_SUCCESS;
    }
}