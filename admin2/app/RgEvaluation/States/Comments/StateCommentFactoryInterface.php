<?php

namespace App\RgEvaluation\States\Comments;

use App\RgEvaluation\Triggers\TriggerInterface;
use Silex\Application;

interface StateCommentFactoryInterface
{
    public static function create(Application $app, TriggerInterface $trigger, string $type): StateCommentInterface;
}