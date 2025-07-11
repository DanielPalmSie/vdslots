<?php

declare(strict_types=1);

namespace Videoslots\User\Factories;

use DBUser;
use Videoslots\User\Services\UpdateProfileAccountService;
use Videoslots\User\Services\UpdateProfileAccountServiceInterface;

final class UpdateProfileAccountServiceFactory
{
    /**
     * @param \DBUser $user
     *
     * @return \Videoslots\User\Services\UpdateProfileAccountServiceInterface
     */
    public static function create(DBUser $user): UpdateProfileAccountServiceInterface
    {
        return new UpdateProfileAccountService($user);
    }
}
