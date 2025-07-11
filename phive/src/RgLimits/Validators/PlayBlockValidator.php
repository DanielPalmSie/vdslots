<?php

declare(strict_types=1);

namespace Videoslots\RgLimits\Validators;

use Videoslots\RgLimits\RgLimitsService;
use Videoslots\RgLimits\Traits\RgLimitValidationHelper;

final class PlayBlockValidator implements RgLimitsValidatorInterface
{
    use RgLimitValidationHelper;

    /**
     * @var string
     */
    private const TYPE = "play_block";

    /**
     * @var int
     */
    private const CODE = 105;

    /**
     * @param \Videoslots\RgLimits\RgLimitsService $rgLimitsService
     * @return array
     */
    public function validate(RgLimitsService $rgLimitsService): array
    {
        $playBlocked = $rgLimitsService->getUser()->isPlayBlocked();
        if (! $playBlocked) {
            return [];
        }

        $content = [
            $this->createContent('simple.1009.html', 'alias', []),
        ];

        return $this->createResponse($content);
    }
}
