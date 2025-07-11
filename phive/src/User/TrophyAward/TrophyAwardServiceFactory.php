<?php

declare(strict_types=1);

namespace Videoslots\User\TrophyAward;

final class TrophyAwardServiceFactory
{
    /**
     * @return \Videoslots\User\TrophyAward\TrophyAwardServiceInterface
     */
    public static function create(): TrophyAwardServiceInterface
    {
        return new TrophyAwardService();
    }
}
