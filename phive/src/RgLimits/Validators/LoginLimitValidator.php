<?php

declare(strict_types=1);

namespace Videoslots\RgLimits\Validators;

use Videoslots\RgLimits\RgLimitsService;
use Videoslots\RgLimits\Traits\RgLimitValidationHelper;

final class LoginLimitValidator implements RgLimitsValidatorInterface
{
    use RgLimitValidationHelper;

    /**
     * @var string
     */
    private const TYPE = "login-limit.info";

    /**
     * @var int
     */
    private const CODE = 109;

    /**
     * @param \Videoslots\RgLimits\RgLimitsService $rgLimitsService
     * @return array
     */
    public function validate(RgLimitsService $rgLimitsService): array
    {
        return []; // This limit is active only on dev environments, that's why it's disabled at the moment.
        if (! lic('checkLoginLimit', $rgLimitsService->getUser())) {
            return [];
        }

        $content = [
            $this->createContent('rg.login.limits.set.headline', 'alias', []),
            $this->createContent('rg.login.limits.set.title', 'alias', []),
            $this->createContent('rg.login.limits.set.deposit.description.part1', 'alias', []),
            $this->createContent('rg.login.limits.set.deposit.description.part2', 'alias', []),
        ];

        return $this->createResponse($content);
    }
}
