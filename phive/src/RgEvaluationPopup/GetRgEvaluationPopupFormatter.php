<?php

declare(strict_types=1);

namespace Videoslots\RgEvaluationPopup;

use Laraphive\Domain\User\DataTransferObjects\RgEvaluationPopup\ActionsData;
use Laraphive\Domain\User\DataTransferObjects\RgEvaluationPopup\ButtonData;
use Laraphive\Domain\User\DataTransferObjects\RgEvaluationPopup\DescriptionData;
use Laraphive\Domain\User\DataTransferObjects\RgEvaluationPopup\HeaderData;
use Laraphive\Support\Settings\RedirectActionSettings;
use Laraphive\Support\Settings\RedirectPagesSettings;

class GetRgEvaluationPopupFormatter
{
    /**
     * @param \Laraphive\Domain\User\DataTransferObjects\RgEvaluationPopup\HeaderData $data
     *
     * @return array
     */
    public static function formatHeaderData(HeaderData $data): array
    {
        return [
            'force_accept' => $data->isForceAccept(),
            'headline' => $data->getHeadline(),
        ];
    }

    /**
     * @param \Laraphive\Domain\User\DataTransferObjects\RgEvaluationPopup\DescriptionData $data
     *
     * @return array
     */
    public static function formatDescriptionData(DescriptionData $data): array
    {
        return [
            'alias' => $data->getAlias(),
            'logo' => $data->getLogoData()->getPath(),
            'dynamic_variables' => $data->getDynamicVariables(),
        ];
    }

    /**
     * @param \Laraphive\Domain\User\DataTransferObjects\RgEvaluationPopup\ActionsData $data
     *
     * @return array
     */
    public static function formatActionsData(ActionsData $data): array
    {
        return [
            self::formatButton($data->getContinueButtonData()),
            self::formatButton($data->getEditLimitsButtonData()),
            self::formatButton($data->getTakeBrakeButtonData()),
        ];
    }

    /**
     * @param \Laraphive\Domain\User\DataTransferObjects\RgEvaluationPopup\ButtonData $data
     *
     * @return array
     */
    private static function formatButton(ButtonData $data): array
    {
        $result = [
            'type' => $data->getType(),
            'alias' => $data->getAlias(),
        ];

        if ($data->getPage() !== null) {
            $result['redirect_to_page'] = RedirectPagesSettings::getRedirectPage($data->getPage());
        }

        if ($data->getAction() !== null) {
            $result['action'] = RedirectActionSettings::getRedirectActions($data->getAction());
        }

        return $result;
    }
}