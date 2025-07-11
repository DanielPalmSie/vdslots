<?php

declare(strict_types=1);

namespace Videoslots\RgLimits\Factories;

use DBUser;
use Laraphive\Domain\Casino\DataTransferObjects\GameData;
use Videoslots\RgLimits\RgLimitsValidator;
use Videoslots\RgLimits\Validators\CategoryLocked24Validator;
use Videoslots\RgLimits\Validators\DepositLimitValidator;
use Videoslots\RgLimits\Validators\ForceDepositLimitValidator;
use Videoslots\RgLimits\Validators\KeepItFunValidator;
use Videoslots\RgLimits\Validators\LoginLimitValidator;
use Videoslots\RgLimits\Validators\PlayBlockValidator;
use Videoslots\RgLimits\Validators\RealityCheckValidator;
use Videoslots\RgLimits\Validators\SelfAssesmentValidator;
use Videoslots\RgLimits\Validators\SessionBalanceValidator;

final class RgLimitsValidatorFactory
{
    /**
     * @param \DBUser $user
     * @param \Laraphive\Domain\Casino\DataTransferObjects\GameData $gameData
     * @return \Videoslots\RgLimits\RgLimitsValidator
     */
    public static function createValidatorForGameLaunch(DBUser $user, GameData $gameData): RgLimitsValidator
    {
        $validators = [
            new KeepItFunValidator(),
            new CategoryLocked24Validator($gameData),
            new DepositLimitValidator(),
            new PlayBlockValidator(),
            new SelfAssesmentValidator(),
            new ForceDepositLimitValidator(),
            new SessionBalanceValidator($gameData),
            new LoginLimitValidator(),
        ];

        $rgLimitsService = RgLimitsServiceFactory::createRgLimitsServiceFactory($user);

        return new RgLimitsValidator($rgLimitsService, $validators);
    }
}
