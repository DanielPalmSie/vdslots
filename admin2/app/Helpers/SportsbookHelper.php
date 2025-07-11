<?php

namespace App\Helpers;

use App\Extensions\Database\FManager as DB;
use App\Models\User;

class SportsbookHelper
{
    public static function hasSportsbookEnabled(User $user): bool
    {
        return lic('isSportsbookEnabled', [], $user->getKey());
    }

    public static function shouldRunSbSetupSeeder(): bool
    {
        return getenv('SB_SETUP_MODE') === 'true';
    }
}
