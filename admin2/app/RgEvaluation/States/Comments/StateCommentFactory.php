<?php

namespace App\RgEvaluation\States\Comments;

use App\RgEvaluation\Triggers\TriggerInterface;
use RuntimeException;
use Silex\Application;

class StateCommentFactory implements StateCommentFactoryInterface
{
    public const ON_SUCCESS = 'onSuccess';
    public const ON_FAILURE = 'onFailure';

    /**
     * @param Application      $app
     * @param TriggerInterface $trigger
     * @param string           $type
     *
     * @return StateCommentInterface
     * @throws RuntimeException
     */
    public static function create(Application $app, TriggerInterface $trigger, string $type): StateCommentInterface
    {
        switch ($type) {
            case self::ON_SUCCESS:
                return new SuccessfulStateComment($app, $trigger);
            case self::ON_FAILURE:
                return new FailedStateComment($app, $trigger);
            default:
                throw new RuntimeException('Invalid state comment type.');
        }
    }
}