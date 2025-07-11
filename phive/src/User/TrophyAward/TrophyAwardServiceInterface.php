<?php

namespace Videoslots\User\TrophyAward;

use DBUser;

interface TrophyAwardServiceInterface
{
    /**
     * @param int $awardId
     * @param \DBUser $user
     * @param bool $translate
     * @param bool $returnMobileGameLaunchUrl
     *
     * @return array
     */
    public function activateTrophyAward(int $awardId, DBUser $user, bool $translate, ?bool $returnMobileGameLaunchUrl = false): array;
}
