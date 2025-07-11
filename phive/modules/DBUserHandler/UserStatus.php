<?php

/**
 * This class contains the "Internal Status" of the user that is stored on users_settings
 * The main purpose, for now, is to simplify filtering the users by status for reporting purposes (Ex. spain ICS report)
 *
 * In the future we can probably rework some of the existing users_setting related to statuses (Ex. restrict, superblock, etc...)
 * to use this unique setting, and simplify/uniform the existing logic.
 */
class UserStatus
{
    /**
     * Initial status before the customer is registered (used to map first change_status action)
     */
    const STATUS_NA = 'NA';
    /**
     * Standard status of an active player, usually achieved after @see DBUser::verify() action is take on the player.
     * Ex. After document verification OR when externally verified (DK,SE)
     */
    const STATUS_ACTIVE = 'ACTIVE';
    /**
     * Initial status of a registered player not externally verified.
     * A user can be moved back into this status if from BO new documents are requested or the user is manually @see DBUser::unVerify()
     */
    const STATUS_PENDING_VERIFICATION = 'PENDING_VERIFICATION';
    /**
     * When a user is marked as "forgotten" from the BO.
     * for ICS: Status of a user who has not been active for 2 years - TODO implement CRON as defined on ch87153
     */
    const STATUS_SUSPENDED = 'SUSPENDED';
    /**
     * When a user is marked as "deleted" from the BO.
     * for ICS: Status of a user who has been in suspended status for 4 years - TODO implement CRON as defined on ch87153
     */
    const STATUS_CANCELED = 'CANCELED';
    /**
     * Status of a user who is not active, but doesn't have any block assigned.
     * Ex. A customer returning from internal self-exclusion, will require a manual activation from CS once his excluded period expire.
     *     A customer that was superblocked, when removing super block the account need 7 days before getting activated (cooloff)
     */
    const STATUS_DORMANT = 'DORMANT';
    /**
     * A status for a user that is under suspect of collusion/fraudolent behaviour.
     * Currently applied only on "manual fraud flag" - TODO see if we need to implement this automatically on some RG/AML flags
     */
    const STATUS_UNDER_INVESTIGATION = 'UNDER_INVESTIGATION';
    /**
     * A status for a user that is found doing fraud.
     * TODO define which action shall mark the customer in this status (maybe move "manual fraud flag" here and the above is only with some RG/AML??)
     */
    const STATUS_BLOCKED_FOR_FRAUD = 'BLOCKED_FOR_FRAUD';
    /**
     * Status for user who decide to externally self-exclude (Ex. gamstop, spelpaus, rofus, etc...)
     * This is handled on standard block procedure @see DBUserHandler::addBlock() with reason 13.
     * @see DBUserHandler::externalSelfExclude()
     */
    const STATUS_EXTERNALLY_SELF_EXCLUDED = 'EXTERNALLY_SELF_EXCLUDED'; // external self excluded (handled in block type 13)

    /**
     * Status for when Lock Account is used, in their RG profile page
     * This is handled in @see DBUserHandler::addBlock() as reason 4, but with an unlock date set.
     * @see DBUserHandler::unlockLocked()
     */
    const STATUS_SELF_LOCKED = 'SELF_LOCKED';

    /**
     * Status for user who decide to self-exclude, via the section provided in the RG profile page.
     * This is handled on standard block procedure @see DBUserHandler::addBlock() with reason 4.
     * @see DBUserHandler::selfExclude()
     */
    const STATUS_SELF_EXCLUDED = 'SELF_EXCLUDED';
    /**
     * Restricted user status is applied via @see DBUser::restrict() either:
     * - automatically on logic defined on @see DBUserHandler::doCheckRestrict()
     * - or manually from BO
     * When a user is @see DBUser::unRestrict() is moved back into ACTIVE status.
     */
    const STATUS_RESTRICTED = 'RESTRICTED';
    /**
     * Generic block reason applied from @see DBUserHandler::addBlock() logic
     */
    const STATUS_BLOCKED = 'BLOCKED';
    /**
     * Super blocked status can be applied from BO or in case of CheckFraud during deposit/withdrawal operation.
     * In case of "SELF_EXCLUDED/EXTERNALLY_SELF_EXCLUDED" super-block setting is assigned to the player, but we are not overriding the STATUS
     * @see DBUser::superBlock()
     */
    const STATUS_SUPERBLOCKED = 'SUPERBLOCKED';

    /**
     * Status for a user that has been marked as deceased from third party service
     */
    const STATUS_DECEASED = 'DECEASED';

    /**
     * This list down the expected number of settings left within user's user_setting table either:
     * - In case of user is falling within the scenario of being Restricted @see UserStatus::STATUS_RESTRICTED , right after decide to
     *   self-lock himself @see UserStatus::STATUS_BLOCKED and due to internal logic ending up being marked
     *   with status self-excluded @see UserStatus::STATUS_SELF_EXCLUDED
     * - In case of user being Externally Self-Excluded @see UserStatus::STATUS_EXTERNALLY_SELF_EXCLUDED
     */
    const USER_EXPECTED_SETTINGS = [
        self::STATUS_SELF_EXCLUDED => ['unlock-date', 'lock-date', 'lock-hours'],
        self::STATUS_EXTERNALLY_SELF_EXCLUDED => ['external-excluded'],
    ];

    private static $status_list;

    /**
     * Return an array with all the constants for the user statuses (STATUS_xxx)
     *
     * @return array
     */
    public static function getStatuses(): array
    {
        if(!isset(self::$status_list)) {
            $class = new ReflectionClass(__CLASS__);
            $constants = $class->getConstants();
            self::$status_list = array_filter($constants, static function ($key) {
                return strpos($key, 'STATUS_') === 0;
            }, ARRAY_FILTER_USE_KEY);
        }
        return self::$status_list;
    }

    /**
     * In some scenarios we don't want the status of the user to be updated, as the current status takes priority.
     *
     * Ex. SELF_EXCLUDED is a greater locking status compared to BLOCKED|RESTRICTED
     *     so if there is an action to to change STATUS to a less priority one we want to prevent that.
     *
     * TODO review the list and check for improvements /Paolo
     *
     * @param DBUser $user
     * @param string $from - UserStatus constant
     * @param string $to - UserStatus constant
     * @return bool
     */
    public static function isAllowedStatusChange(DBUser $user, string $from, string $to): bool
    {
        $settings = self::USER_EXPECTED_SETTINGS[$from] ?? [];
        if (!empty($settings)) {
            $u_settings = $user->getSettingsIn($settings);
        }

        $all_statuses_except_under_investigation = array_filter(self::getStatuses(), function ($status) {
            return $status !== self::STATUS_UNDER_INVESTIGATION;
        });

        $status_changes_to_prevent = [
            self::STATUS_DECEASED => $all_statuses_except_under_investigation,
            self::STATUS_SELF_LOCKED => [self::STATUS_RESTRICTED, self::STATUS_BLOCKED],
            self::STATUS_SELF_EXCLUDED => [self::STATUS_RESTRICTED, self::STATUS_BLOCKED, self::STATUS_SELF_LOCKED],
            self::STATUS_EXTERNALLY_SELF_EXCLUDED => [self::STATUS_RESTRICTED, self::STATUS_BLOCKED]
        ];
        if (!empty($u_settings) && isset($status_changes_to_prevent[$from]) && in_array($to,$status_changes_to_prevent[$from])) {
            phive('Logger')->log('user_status_prevent',
                [
                    'message' => "Tried to change status from {$from} to {$to}, but prevented as it's less priority",
                    'user' => $user->getId()
                ]);
            return false;
        }
        // if unverified and self-locked user, with current_status valued SELF_EXCLUDED wants to change it to RESTRICTED and lock settings were already deleted: reactivate it
        if ($from === self::STATUS_SELF_EXCLUDED && $to === self::STATUS_RESTRICTED && $user->isRestricted()) {
            $user->setAttribute('active', '1');
            phive('Logger')->log('user_status_prevent',
                [
                    'message' => "Changed status from {$from} to {$to}, lock settings for X-days were already deleted, User set as active",
                    'user' => $user->getId()
                ]);
        }
        return true;
    }
}
