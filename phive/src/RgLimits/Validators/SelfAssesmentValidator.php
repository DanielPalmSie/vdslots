<?php

declare(strict_types=1);

namespace Videoslots\RgLimits\Validators;

use Videoslots\RgLimits\RgLimitsService;
use Videoslots\RgLimits\Traits\RgLimitValidationHelper;

final class SelfAssesmentValidator implements RgLimitsValidatorInterface
{
    use RgLimitValidationHelper;

    /**
     * @var string
     */
    private const TYPE = "self_assesment";

    /**
     * @var int
     */
    private const CODE = 107;

    /**
     * @param \Videoslots\RgLimits\RgLimitsService $rgLimitsService
     * @return array
     */
    public function validate(RgLimitsService $rgLimitsService): array
    {
        $triggerForceSelfAssesmentPopup = $rgLimitsService->getUser()->getSetting('force_self_assessment_test');
        if (! $triggerForceSelfAssesmentPopup) {
            return [];
        }

        $content = [
            $this->createContent('rg.self_assesment.popup.title', 'alias', []),
        ];

        return $this->createResponse($content);
    }
}
