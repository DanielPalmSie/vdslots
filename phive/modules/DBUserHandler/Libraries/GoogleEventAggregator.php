<?php

namespace DBUserHandler\Libraries;

use DBUser;
use DBUserHandler\Libraries\GoogleAnalytics\Constants;
use DBUserHandler\Libraries\GoogleAnalytics\Actions\CompletedLogin;
use DBUserHandler\Libraries\GoogleAnalytics\Actions\CompletedRegistration;
use DBUserHandler\Libraries\GoogleAnalytics\Actions\FirstDepositEvent;
use DBUserHandler\Libraries\GoogleAnalytics\Actions\PartialRegistration;
use DBUserHandler\Libraries\GoogleAnalytics\Actions\SubsequentDepositEvent;
use DBUserHandler\Libraries\GoogleAnalytics\Actions\StartedFirstDepositEvent;
use DBUserHandler\Libraries\GoogleAnalytics\Actions\StartedSubsequentDepositEvent;
use DBUserHandler\Libraries\GoogleAnalytics\Actions\PRRoomEvent;

class GoogleEventAggregator
{
    public static function getEvents(): array
    {
        return [
            Constants::KEY_STARTED_FIRST_DEPOSIT,
            Constants::KEY_STARTED_SUBSEQUENT_DEPOSIT,
            Constants::KEY_FIRST_DEPOSIT,
            Constants::KEY_SUBSEQUENT_DEPOSIT,
            Constants::KEY_COMPLETED_REGISTRATION,
            Constants::KEY_PARTIALLY_REGISTERED,
            Constants::KEY_LOGGED_IN
        ];
    }

    public static function getAnalyticsObject(?DBUser $user, string $event_key): array
    {
        if(empty($user)) {
            return [];
        }

        if (!phive('Cookie')->analyticsCookiesEnable()) {
            return [];
        }

        switch ($event_key) {

            case Constants::KEY_STARTED_FIRST_DEPOSIT:
                $event_object = (new StartedFirstDepositEvent($user));
                break;

            case Constants::KEY_STARTED_SUBSEQUENT_DEPOSIT:
                $event_object = (new StartedSubsequentDepositEvent($user));
                break;

            case Constants::KEY_FIRST_DEPOSIT:
                $event_object = (new FirstDepositEvent($user));
                break;

            case Constants::KEY_SUBSEQUENT_DEPOSIT:
                $event_object = (new SubsequentDepositEvent($user));
                break;

            case Constants::KEY_PARTIALLY_REGISTERED:
                $event_object = (new PartialRegistration($user));
                break;

            case Constants::KEY_COMPLETED_REGISTRATION:
                $event_object = (new CompletedRegistration($user));
                break;

            default:
                $event_object = (new CompletedLogin($user));
                break;

        }

        return $event_object->getAnalyticsObject();
    }

    /**
     * Remove the event keys from redis after an event has been fired.
     *
     * @param array $events
     * @param DBUser|null $user
     */
    public static function removeKeys(array $events, ?DBUser $user)
    {
        $events = is_array($events) ? $events : [[$events]];
        if (empty($user)) {
            return;
        }
        foreach ($events as $key) {
            $redis_event = $user->getTrackingEvent($key);

            if (empty($redis_event)) {
                continue;
            }

            //removing the data in Redis when both events are fired
            if (self::isDeletableKey($redis_event)) {
                $user->removePixelKey($key);
            }
        }
    }

    /**
     * Check if the variable in Redis can be deleted
     *
     * @param array $redis_event
     * @return bool
     */
    private static function isDeletableKey(array $redis_event): bool
    {
        return ($redis_event['analytics'] == true && $redis_event['pixel'] == true && $redis_event['pr_room'] == true) || (!isExternalTrackingEnabled());
    }
}
