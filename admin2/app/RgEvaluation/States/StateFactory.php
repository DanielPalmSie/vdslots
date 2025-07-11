<?php

namespace App\RgEvaluation\States;

use RuntimeException;
use Silex\Application;

class StateFactory
{
    /**
     * @param Application $application
     * @param string      $state
     *
     * @return StateInterface
     * @throws RuntimeException
     */
    public static function create(Application $application, string $state): StateInterface
    {
        switch ($state) {
            case State::CHECK_USERS_GRS_STATE:
                return new CheckUsersGRSState($application);
            case State::CHECK_ACTIVITY_STATE:
                return new CheckActivityState($application);
            case State::FORCE_SELF_ASSESSMENT_STATE:
                return new ForceSelfAssessmentState($application);
            case State::TRIGGER_MANUAL_REVIEW_STATE:
                return new TriggerManualReviewState($application);
            case State::FINAL_ACTION_STATE:
                return new FinalActionState($application);
            case State::NO_ACTION_STATE:
                return new NoActionState($application);
            default:
                throw new RuntimeException("Unknown state '$state'");
        }
    }
}