<?php

require_once __DIR__ . '/Bonuses.php';

/**
 * Class ExclusiveHandler
 *
 * Helper class to handle bonus activation in terms
 * of exclusive and non-exclusive relationships
 *
 * Type 0: not exclusive, can not be used with active exclusives or reactivated
 * Type 1: super exclusive, can not be reactivated, cannot be used with other exclusive, will fail non-exclusive when activated
 * Type 2: not exclusive, can not be used with other active exclusives, can be reactivated, can not have other type 2s active unless activating a free-spins bonus
 * Type 3: not exclusive, can be used with other active exclusives, can not be reactivated, can have other type 3s active
 * Type 4: not exclusive, can be used with other active exclusives, can be reactivated, can have other type 4s active
 *
 */
class ExclusiveHandler extends Bonuses {

    public const STATE_PENDING = 'pending';
    public const STATE_ACTIVE = 'active';
    public const STATE_APPROVED = 'approved';
    public const STATE_COMPLETED = 'completed';
    public const STATE_FAILED = 'failed';
    public const STATE_CORRUPTED = 'corrupted';
    public const STATE_EXPIRED = 'expired';

    public const TYPE_0 = 0; // Not exclusive
    public const TYPE_1 = 1; // Exclusive
    public const TYPE_2 = 2; // Not exclusive but one active at a time
    public const TYPE_3 = 3; // Not exclusive, allows exclusives
    public const TYPE_4 = 4; // Not exclusive, allows exclusives, one active at a time

    public const FREE_SPIN_TYPE = 'free-spin';
    public const EXCLUSIVE_TYPES = [self::TYPE_1];
    public const CAN_ACTIVATE_WITH_EXCLUSIVE = [self::TYPE_3, self::TYPE_4];
    public const CAN_REACTIVATE = [self::TYPE_2, self::TYPE_4];
    public const CAN_HAVE_MULTIPLE = [self::TYPE_3, self::TYPE_4];
    public const FORFEIT_OTHERS_WHEN_ACTIVATED = [
        self::TYPE_1 => [self::TYPE_0, self::TYPE_1, self::TYPE_2, self::TYPE_3, self::TYPE_4]
    ];

    /**
     * @param DBUser|int|null $user
     * @param array $bonus
     * @param bool $throwErrors
     * @return bool
     */
    public function canActivateBonus($user, array $bonus, bool $throwErrors = true): bool
    {
        try {
            return $this->canActivate($user, (int) $bonus['id'], (int) $bonus['exclusive'], (int) $bonus['bonus_type']);
        } catch (LogicException $e) {
            if ($throwErrors) throw $e;
            else return false;
        }
    }

    /**
     * Checks if the current user can activate the provided
     * bonuses based on the exclusive and type provided, against
     * his/her current bonuses and past bonus entry data
     *
     * @param DBUser|int|null $user
     * @param int $bonus_id
     * @param int $exclusive_type
     * @param string $bonus_type
     * @return true
     * @throws LogicException - If the bonus cannot be activated
     */
    public function canActivate($user, int $bonus_id, int $exclusive_type, string $bonus_type): bool
    {
        $active = $this->getActiveBonuses($user);

        // Same and active, fail activation, ie impossible to have two instances of the same bonus active at the same time.
        if ($this->getCountBy($active, 'bonus_id', [$bonus_id]) > 0) {
            throw new LogicException("Cannot activate bonus {$bonus_id}, user already has it active or pending");
        }

        if (!in_array($exclusive_type, self::CAN_ACTIVATE_WITH_EXCLUSIVE)
            && $this->getCountBy($active, 'exclusive', self::EXCLUSIVE_TYPES) > 0
        ) {
            throw new LogicException(
                "Cannot activate bonus {$bonus_id} with exclusive type {$exclusive_type}, user already has active exclusive bonuses"
            );
        }

        if (!in_array($exclusive_type, self::CAN_REACTIVATE) && $this->hadActivated($user, $bonus_id)) {
            throw new LogicException(
                "Cannot activate bonus {$bonus_id} with exclusive type {$exclusive_type}, user already has already activated it in the past"
            );
        }

        if (!in_array($exclusive_type, self::CAN_HAVE_MULTIPLE)
            && $this->getCountBy($active, 'exclusive', [$exclusive_type]) > 0
        ) {
            if ($exclusive_type === self::TYPE_2 && strtolower(trim($bonus_type)) === self::FREE_SPIN_TYPE) {
                return true;
            }

            throw new LogicException(
                "Cannot activate bonus {$bonus_id} with exclusive type $exclusive_type, user already has other bonuses with type $exclusive_type active or pending"
            );
        }

        return true;
    }

    /**
     * @param array $bonus
     * @param DBUser|int|null $user
     * @param string $log
     * @return void
     */
    public function failOthersToActivate(array $bonus, $user, string $log)
    {
        if (!array_key_exists((int) $bonus['exclusive'], self::FORFEIT_OTHERS_WHEN_ACTIVATED)) return;

        $fail = $this->getSQLHandler($user)->makeIn(self::FORFEIT_OTHERS_WHEN_ACTIVATED[(int) $bonus['exclusive']]);
        $active = $this->getActiveBonuses($user, "bt.exclusive IN({$fail})");

        if (count($active) > 0) {
            /** @var CasinoBonuses $bh */
            $bh = phive('CasinoBonuses');

            foreach ($active as $bonus) {
                $bh->fail($bonus['entry_id'], sprintf($log, $bonus['entry_id']));
            }
        }
    }

    /**
     * @param DBUser|int|null $user
     * @param int $bonus_id
     * @return bool
     */
    private function hadActivated($user, int $bonus_id): bool
    {
        $user = cu($user);
        if (empty($user)) return false;

        return $this->getSQLHandler($user)->getValue(sprintf(
            "SELECT COUNT(*) FROM bonus_entries WHERE user_id = %d AND bonus_id = %d",
            $user->getId(), $bonus_id
        )) > 0;
    }

    private function getCountBy(array $bonuses, string $column, array $search, bool $notIn = false): int
    {
        return count(array_filter($bonuses, function ($item) use ($column, $search, $notIn) {
            return ($notIn) ? !in_array($item[$column], $search) : in_array($item[$column], $search);
        }));
    }

    /**
     * @param DBUser|int|null $user
     * @param string $where
     * @return array
     */
    private function getActiveBonuses($user, string $where = ''): array
    {
        $user = cu($user);
        if (empty($user)) return [];

        return $this->getSQLHandler($user)->loadArray(sprintf("
            SELECT be.id AS entry_id,
                   bt.id AS bonus_id,
                   be.status AS status,
                   bt.exclusive AS exclusive
            FROM bonus_entries AS be
            JOIN bonus_types AS bt ON be.bonus_id = bt.id
            WHERE be.user_id = %s
              AND be.status IN(%s) %s
            ",
            $user->getId(),
            $this->getSQLHandler($user)->makeIn([self::STATE_ACTIVE, self::STATE_PENDING]),
            $where ? "AND " . $where : ""
        ));
    }

    /**
     * @param DBUser $user
     * @return SQL
     */
    private function getSQLHandler(DBUser $user): SQL
    {
        return phive('SQL')->sh($user->getId());
    }
}
