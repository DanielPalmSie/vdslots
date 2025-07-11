<?php

declare(strict_types=1);

namespace Videoslots\User\TrophyBonus;

use CasinoBonuses;

final class TrophyBonusService
{
    /**
     * @var string
     */
    public const COLUMN_STATUS = 'status';

    /**
     * @var string
     */
    public const STATUS_ACTIVE = "active";

    /**
     * @var string
     */
    public const ERROR_NOT_AN_OWNER = "api.user.error.not.an.owner";

    /**
     * @var string
     */
    public const ERROR_USER_STILL_PLAYING = "api.user.error.player.is.playing";

    /**
     * @var string
     */
    public const ERROR_BONUS_IS_NOT_ACTIVE = "api.user.error.bonus.is.not.active";

    /**
     * @var string
     */
    public const ERROR_BONUS_NOT_FOUND = "api.user.error.bonus.not.found";

    /**
     * @var string
     */
    private const ERROR_TROPHY_NOT_ALLOWED_TO_FORFEIT = "api.user.error.trophy.not.allowed.forfeit";

    /**
     * @var string
     */
    public const ERROR_TROPHY_NOT_FOUND = "api.user.error.trophy.not.found";

    /**
     * @var array|null
     */
    private ?array $entry;

    /**
     * @var int
     */
    private int $userId;

    public function __construct()
    {
        $this->userId = intval(cu()->getId());
    }

    /**
     * @param int $id
     *
     * @return string|null
     */
    public function forfeitBonus(int $id): ?string
    {
        $this->entry = $this->findBonus($id);

        if ($this->entry === null) {
            return self::ERROR_BONUS_NOT_FOUND;
        }

        if ($this->canRemoveBonus()) {
            return self::ERROR_NOT_AN_OWNER;
        }

        if ($this->isPlayerPlayingNow()) {
            return self::ERROR_USER_STILL_PLAYING;
        }

		phive('CasinoBonuses')->handleBonusForfeit($this->entry);
		
        $this->deleteBonusEntry();

        return null;
    }

    /**
     * @param int $id
     *
     * @return string|null
     */
    public function forfeitTrophy(int $id): ?string
    {
        $trophy = phive('Trophy');
        $award = array_pop($trophy->getUserAwards(cu($this->userId), ' = 0', '', $id));

        if(is_null($award)) {
            return self::ERROR_TROPHY_NOT_FOUND;
        }

        if($award['type'] == 'mp-freeroll-ticket') {
            return self::ERROR_TROPHY_NOT_ALLOWED_TO_FORFEIT;
        }

        $trophy->deleteNonActiveAward($this->userId, $id, 2);

        return null;
    }

    /**
     * @return bool
     */
    private function canRemoveBonus(): bool
    {
        return ! p('account.removebonus') && intval($this->entry['user_id']) !== $this->userId;
    }

    /**
     * @return bool
     */
    private function isPlayerPlayingNow(): bool
    {
        return phive("Casino")->checkPlayerIsPlayingAGame($this->userId);
    }

    /**
     * @return void
     */
    private function deleteBonusEntry(): void
    {
        $message = sprintf(
            "Bonus with id %d failed by %s",
            intval($this->entry['bonus_id']),
            cu()->getUsername()
        );

        phive('Bonuses')->fail($this->entry['id'], $message, $this->userId);
    }

    /**
     * @param int $id
     *
     * @return array|null
     */
    private function findBonus(int $id): ?array
    {
        return $this->entry = phive('Bonuses')->getBonusEntry($id, $this->userId);
    }

    /**
     * @param int $userId
     *
     * @return array
     */
    /**
     * Retrieves the bonuses associated with a specific user.
     *
     * @param int $userId The unique identifier of the user.
     * @return array An array of bonuses associated with the user.
     */
    public function getUserBonuses(int $userId): array
    {
        return phive('Bonuses')->getUserBonuses($userId, '', '', "IN('casino', 'casinowager')", true) ?? [];
    }

    /**
     * @return string|null
     */
    public function forfeitBonusesToDeposit(): ?string
    {
        $user = cu();
        $bonuses = $user->getBonusesToForfeitBeforeDeposit();
        if (count($bonuses) < 1) return null;

        /** @var CasinoBonuses $bs */
        $bs = phive('Bonuses');

        foreach ($bonuses as $bonus_id) {
            if (!$bs->fail($bonus_id, 'Forfeited to Deposit', $user->getId())) {
                return 'forfeit.deposit.error';
            }
        }

        return null;
    }
}
