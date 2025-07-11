<?php

declare(strict_types=1);

namespace Videoslots\RgLimits\Validators;

use Videoslots\RgLimits\RgLimitsService;
use Videoslots\RgLimits\Traits\RgLimitValidationHelper;

final class ForceDepositLimitValidator implements RgLimitsValidatorInterface
{
    use RgLimitValidationHelper;

    /**
     * @var string
     */
    private const TYPE = "force_deposit_limit";

    /**
     * @var int
     */
    private const CODE = 106;

    /**
     * @param \Videoslots\RgLimits\RgLimitsService $rgLimitsService
     * @return array
     */
    public function validate(RgLimitsService $rgLimitsService): array
    {
        $forceDepositLimit = $rgLimitsService->getUser()->hasSetting('force_deposit_limit');
        if (! $forceDepositLimit) {
            return [];
        }

        $content = [
            $this->createContent('rg.play_block.popup.title', 'alias', []),
            $this->createContent('rg.play_block.popup.message', 'alias', []),
        ];

        return $this->createResponse($content);
    }
}
