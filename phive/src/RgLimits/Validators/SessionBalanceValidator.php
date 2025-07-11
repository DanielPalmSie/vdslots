<?php

declare(strict_types=1);

namespace Videoslots\RgLimits\Validators;

use Laraphive\Domain\Casino\DataTransferObjects\GameData;
use Videoslots\RgLimits\RgLimitsService;
use Videoslots\RgLimits\Traits\RgLimitValidationHelper;

final class SessionBalanceValidator implements RgLimitsValidatorInterface
{
    use RgLimitValidationHelper;

    /**
     * @var string
     */
    private const TYPE = "session_balance";

    /**
     * @var int
     */
    private const CODE = 108;

    /**
     * @var \Laraphive\Domain\Casino\DataTransferObjects\GameData
     */
    private GameData $gameData;

    public function __construct(GameData $gameData)
    {
        $this->gameData = $gameData;
    }

    /**
     * @param \Videoslots\RgLimits\RgLimitsService $rgLimitsService
     * @return array
     */
    public function validate(RgLimitsService $rgLimitsService): array
    {
        $showSessionBalancePopup = lic(
            'showSessionBalancePopups',
            [$rgLimitsService->getUser(), $this->gameData->toArray(), true, true],
            $rgLimitsService->getUser()
        );

        if (! $showSessionBalancePopup) {
            return [];
        }

        $content = [
            $this->createContent('rg.play_block.popup.title', 'alias', []),
            $this->createContent('rg.play_block.popup.message', 'alias', []),
        ];

        return $this->createResponse($content);
    }
}
