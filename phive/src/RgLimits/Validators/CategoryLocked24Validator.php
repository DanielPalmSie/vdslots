<?php

declare(strict_types=1);

namespace Videoslots\RgLimits\Validators;

use Laraphive\Domain\Casino\DataTransferObjects\GameData;
use Videoslots\RgLimits\RgLimitsService;
use Videoslots\RgLimits\Traits\RgLimitValidationHelper;

final class CategoryLocked24Validator implements RgLimitsValidatorInterface
{
    use RgLimitValidationHelper;

    /**
     * @var string
     */
    private const TYPE = "game-category.locked.info";

    /**
     * @var int
     */
    private const CODE = 104;

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
        $rgLockedGames = $rgLimitsService->getUser()->getRgLockedGames();
        if ((empty(lic('gamebreak_24')) && empty(lic('gamebreak_indefinite'))) && empty($rgLockedGames)) {
            return [];
        }

        $isGameLocked = $rgLimitsService->getUser()->isGameLocked($this->gameData->getTag());
        if (! $isGameLocked) {
            return [];
        }
        $gameCategoryLockedTitle = t('game-category.locked.title') . ': ' . $this->gameData->getTag();
        $content = [
            $this->createContent('game-category.locked.info', 'alias', []),
            $this->createContent($gameCategoryLockedTitle, 'string', []),
        ];

        return $this->createResponse($content);
    }
}
