<?php

declare(strict_types=1);

namespace Videoslots\RgLimits\Validators;

use Videoslots\RgLimits\RgLimitsService;
use Videoslots\RgLimits\Traits\RgLimitValidationHelper;

final class KeepItFunValidator implements RgLimitsValidatorInterface
{
    use RgLimitValidationHelper;

    /**
     * @var string
     */
    private const TYPE = "keep_it_fun";

    /**
     * @var int
     */
    private const CODE = 101;

    /**
     * References logic from spending_amount_box.php
     * @param \Videoslots\RgLimits\RgLimitsService $rgLimitsService
     * @return array
     */
    public function validate(RgLimitsService $rgLimitsService): array
    {
        $hasViewedResponsibleGaming = lic('hasViewedResponsibleGaming', [$rgLimitsService->getUser()]);
        if ($hasViewedResponsibleGaming) {
            return [];
        }

        $content = [
            $this->createContent('rg.spending.popup.title', 'alias', []),
            $this->createContent('rg.spending.popup.top.message', 'alias', []),
            $this->createContent('rg.spending.popup.occupation.label', 'alias', []),
            $this->createContent('rg.spending.popup.occupation.input', 'alias', []),
            $this->createContent('rg.spending.popup.spending.label', 'alias', []),
            $this->createContent('rg.spending.popup.main.message.html', 'alias', []),
            $this->createContent('rg.spending.popup.tick.box.label', 'alias', []),
            $this->createContent('rg.spending.popup.button', 'alias', []),
        ];

        return $this->createResponse($content);
    }
}
