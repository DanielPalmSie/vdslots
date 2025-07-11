<?php

declare(strict_types=1);

namespace Videoslots\User\ThirdPartyVerificationFields\DataTransferObject;

final class TopPartData
{
    /**
     * @var string
     */
    private string $boxId;

    /**
     * @var string
     */
    private string $boxHeadlineAlias;

    /**
     * @var bool
     */
    private bool $hideClose;

    /**
     * @var bool
     */
    private bool $redirectOnMobile;

    /**
     * @var string
     */
    private string $target;

    /**
     * @var bool
     */
    private bool $closeMobileGameOverlay;

    /**
     * @var bool
     */
    private bool $topLeftIcon;

    /**
     * @var bool
     */
    private bool $hasCloseButton;

    /**
     * @var \Videoslots\User\ThirdPartyVerificationFields\DataTransferObject\CloseButtonData
     */
    private CloseButtonData $closeButton;

    /**
     * @param string $boxId
     * @param string $boxHeadlineAlias
     * @param bool $hideClose
     * @param bool $redirectOnMobile
     * @param string $target
     * @param bool $closeMobileGameOverlay
     * @param bool $topLeftIcon
     * @param bool $hasCloseButton
     * @param \Videoslots\User\ThirdPartyVerificationFields\DataTransferObject\CloseButtonData $closeButton
     */
    public function __construct(
        string $boxId,
        string $boxHeadlineAlias,
        bool $hideClose,
        bool $redirectOnMobile,
        string $target,
        bool $closeMobileGameOverlay,
        bool $topLeftIcon,
        bool $hasCloseButton,
        CloseButtonData $closeButton
    ) {
        $this->boxId = $boxId;
        $this->boxHeadlineAlias = $boxHeadlineAlias;
        $this->hideClose = $hideClose;
        $this->redirectOnMobile = $redirectOnMobile;
        $this->target = $target;
        $this->closeMobileGameOverlay = $closeMobileGameOverlay;
        $this->topLeftIcon = $topLeftIcon;
        $this->hasCloseButton = $hasCloseButton;
        $this->closeButton = $closeButton;
    }

    /**
     * @return string
     */
    public function getBoxId(): string
    {
        return $this->boxId;
    }

    /**
     * @return string
     */
    public function getBoxHeadlineAlias(): string
    {
        return $this->boxHeadlineAlias;
    }

    /**
     * @return bool
     */
    public function isHideClose(): bool
    {
        return $this->hideClose;
    }

    /**
     * @return bool
     */
    public function isRedirectOnMobile(): bool
    {
        return $this->redirectOnMobile;
    }

    /**
     * @return string
     */
    public function getTarget(): string
    {
        return $this->target;
    }

    /**
     * @return bool
     */
    public function isCloseMobileGameOverlay(): bool
    {
        return $this->closeMobileGameOverlay;
    }

    /**
     * @return bool
     */
    public function isTopLeftIcon(): bool
    {
        return $this->topLeftIcon;
    }

    /**
     * @return bool
     */
    public function hasCloseButton(): bool
    {
        return $this->hasCloseButton;
    }

    /**
     * @return \Videoslots\User\ThirdPartyVerificationFields\DataTransferObject\CloseButtonData
     */
    public function getCloseButton(): CloseButtonData
    {
        return $this->closeButton;
    }
}
