<?php

declare(strict_types=1);

namespace Videoslots\RgLimits\Validators;

use Videoslots\RgLimits\RgLimitsService;
use Videoslots\RgLimits\Traits\RgLimitValidationHelper;

final class DepositLimitValidator implements RgLimitsValidatorInterface
{
    use RgLimitValidationHelper;

    /**
     * @var string
     */
    private const TYPE = "deposit.limit.banner.info.headline";

    /**
     * @var int
     */
    private const CODE = 102;

    /**
     * @param \Videoslots\RgLimits\RgLimitsService $rgLimitsService
     * @return array
     */
    public function validate(RgLimitsService $rgLimitsService): array
    {
        if (empty(licSetting('deposit_limit', $rgLimitsService->getUser()))) {
            return [];
        }

        if ($rgLimitsService->getRgLimits()->hasLimits($rgLimitsService->getUser(), 'deposit')
            && ! empty(licSetting('deposit_limit', $rgLimitsService->getUser()))) {
            return [];
        }

        $content = [
            $this->createContent(
                'deposit.limit.banner.info.headline',
                'alias',
                []
            ),
            $this->createContent('rg.info.limits.set.title', 'alias', []),
            $this->createContent('rg.info.limits.set.deposit.description.part1', 'alias', []),
            $this->createContent('rg.info.limits.set.deposit.description.part2', 'alias', []),
            $this->createContent('registration.set.limits.text', 'alias', []),
        ];

        return $this->createResponse($content);
    }
}
