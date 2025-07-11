<?php

declare(strict_types=1);

namespace Videoslots\RgLimits\Validators;

use Videoslots\RgLimits\RgLimitsService;
use Videoslots\RgLimits\Traits\RgLimitValidationHelper;

final class RealityCheckValidator implements RgLimitsValidatorInterface
{
    use RgLimitValidationHelper;

    /**
     * @var string
     */
    private const TYPE = "reality-check.locked.info";

    /**
     * @var int
     */
    private const CODE = 103;

    /**
     * @param \Videoslots\RgLimits\RgLimitsService $rgLimitsService
     * @return array
     */
    public function validate(RgLimitsService $rgLimitsService): array
    {
        if ($rgLimitsService->getRgLimits()->hasLimits($rgLimitsService->getUser(), 'rc')) {
            return [];
        }

        $content = [
            $this->createContent('reality-check.locked.info', 'alias', []),
            $this->createContent('reality-check.label.responsibleGaming', 'alias', []),
        ];

        return $this->createResponse($content);
    }
}
