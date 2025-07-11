<?php

declare(strict_types=1);

namespace Videoslots\User\ThirdPartyVerificationFields\Factory;

use Videoslots\User\ThirdPartyVerificationFields\DataTransferObject\CloseButtonData;
use Videoslots\User\ThirdPartyVerificationFields\DataTransferObject\TopPartData;
use Videoslots\User\ThirdPartyVerificationFields\ThirdPartyVerificationFieldsSetting;

final class TopPartFactory
{
    /**
     * @param string $boxId
     * @param string $boxHeadlineAlias
     * @param bool $hideClose
     * @param bool $redirectOnMobile
     * @param string $target
     * @param bool $closeMobileGameOverlay
     * @param bool $topLeftIcon
     *
     * @return \Videoslots\User\ThirdPartyVerificationFields\DataTransferObject\TopPartData
     */
    public function create(
        string $boxId,
        string $boxHeadlineAlias,
        bool $hideClose = false,
        bool $redirectOnMobile = true,
        string $target = 'window',
        bool $closeMobileGameOverlay = false,
        bool $topLeftIcon = false
    ): TopPartData {
        if (empty($boxHeadlineAlias)) {
            $boxHeadlineAlias = ThirdPartyVerificationFieldsSetting::TOP_PART_DEFAULT_HEADER;
        }

        $hasCloseButton = $hideClose === false;

        $closeButton = $this->createCloseButton($hasCloseButton, $redirectOnMobile);

        return new TopPartData(
            $boxId,
            $boxHeadlineAlias,
            $hideClose,
            $redirectOnMobile,
            $target,
            $closeMobileGameOverlay,
            $topLeftIcon,
            $hasCloseButton,
            $closeButton
        );
    }

    /**
     * @param bool $hasCloseButton
     * @param bool $redirectOnMobile
     *
     * @return \Videoslots\User\ThirdPartyVerificationFields\DataTransferObject\CloseButtonData
     */
    private function createCloseButton(bool $hasCloseButton, bool $redirectOnMobile): CloseButtonData
    {
        $factory = new CloseButtonFactory();
        if (! $hasCloseButton) {
            return $factory->empty();
        }

        $doRedirect = phive()->isMobile() === true && $redirectOnMobile === true;

        return $factory->create($doRedirect, llink("/"));
    }
}
