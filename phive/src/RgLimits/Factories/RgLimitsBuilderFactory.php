<?php

declare(strict_types=1);

namespace Videoslots\RgLimits\Factories;

use DBUser;
use Videoslots\RgLimits\Builders\Gamebreak24Builder;
use Videoslots\RgLimits\Builders\GamebreakIndefiniteBuilder;
use Videoslots\RgLimits\Builders\GroupedResetablesBuilder;
use Videoslots\RgLimits\Builders\IndefiniteSelfExclusion;
use Videoslots\RgLimits\Builders\LockBuilder;
use Videoslots\RgLimits\Builders\ResetablesBuilder;
use Videoslots\RgLimits\Builders\SelfAssessmentBuilder;
use Videoslots\RgLimits\Builders\SelfExclusionBuilder;
use Videoslots\RgLimits\Builders\SelfExclusionPermanentlyBuilder;
use Videoslots\RgLimits\Builders\SingleResetablesBuilder;
use Videoslots\RgLimits\Builders\UndoWithdrawalsBuilder;
use Videoslots\RgLimits\RgLimitsBuilder;

final class RgLimitsBuilderFactory
{
    /**
     * @param \DBUser $user
     * @param string|null $type
     * @param bool $isApi
     * @return \Videoslots\RgLimits\RgLimitsBuilder
     */
    public static function createBuilder(DBUser $user, ?string $type = null, bool $isApi = false): RgLimitsBuilder
    {
        $rgLimitsService = RgLimitsServiceFactory::createRgLimitsServiceFactory($user, $isApi);

        $builders = [
            new SelfAssessmentBuilder(),
            new GroupedResetablesBuilder(),
            new ResetablesBuilder($rgLimitsService, $type),
            new SingleResetablesBuilder(),
            new Gamebreak24Builder(),
            new UndoWithdrawalsBuilder(),
            new GamebreakIndefiniteBuilder(),
            new LockBuilder(),
            new SelfExclusionBuilder(),
            new SelfExclusionPermanentlyBuilder(),
            new IndefiniteSelfExclusion(),
        ];

        return new RgLimitsBuilder($rgLimitsService, $builders);
    }
}
