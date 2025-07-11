<?php

declare(strict_types=1);

namespace Videoslots\User\ThirdPartyVerificationFields\DataTransferObject;

final class ThirdPartyVerificationFieldsData
{
    /**
     * @var \Videoslots\User\ThirdPartyVerificationFields\DataTransferObject\TopPartData
     */
    private TopPartData $topPartData;

    /**
     * @var string
     */
    private string $customLoginInfo;

    /**
     * @var string
     */
    private string $nidPlaceholder;

    /**
     * @var string
     */
    private string $personalNumberMessage;

    /**
     * @var string
     */
    private string $rememberNidMessage;

    /**
     * @var string
     */
    private string $registerButtonImage;

    /**
     * @var \Videoslots\User\ThirdPartyVerificationFields\DataTransferObject\StartExternalVerificationButtonData
     */
    private StartExternalVerificationButtonData $startExternalVerificationButtonData;

    /**
     * @param \Videoslots\User\ThirdPartyVerificationFields\DataTransferObject\TopPartData $topPartData
     * @param string $customLoginInfo
     * @param string $nidPlaceholder
     * @param string $personalNumberMessage
     * @param string $rememberNidMessage
     * @param string $registerButtonImage
     * @param \Videoslots\User\ThirdPartyVerificationFields\DataTransferObject\StartExternalVerificationButtonData $startExternalVerificationButtonData
     */
    public function __construct(
        TopPartData $topPartData,
        string $customLoginInfo,
        string $nidPlaceholder,
        string $personalNumberMessage,
        string $rememberNidMessage,
        string $registerButtonImage,
        StartExternalVerificationButtonData $startExternalVerificationButtonData
    ) {
        $this->topPartData = $topPartData;
        $this->customLoginInfo = $customLoginInfo;
        $this->nidPlaceholder = $nidPlaceholder;
        $this->personalNumberMessage = $personalNumberMessage;
        $this->rememberNidMessage = $rememberNidMessage;
        $this->registerButtonImage = $registerButtonImage;
        $this->startExternalVerificationButtonData = $startExternalVerificationButtonData;
    }

    /**
     * @return \Videoslots\User\ThirdPartyVerificationFields\DataTransferObject\TopPartData
     */
    public function getTopPartData(): TopPartData
    {
        return $this->topPartData;
    }

    /**
     * @return string
     */
    public function getCustomLoginInfo(): string
    {
        return $this->customLoginInfo;
    }

    /**
     * @return string
     */
    public function getNidPlaceholder(): string
    {
        return $this->nidPlaceholder;
    }

    /**
     * @return string
     */
    public function getPersonalNumberMessage(): string
    {
        return $this->personalNumberMessage;
    }

    /**
     * @return string
     */
    public function getRememberNidMessage(): string
    {
        return $this->rememberNidMessage;
    }

    /**
     * @return string
     */
    public function getRegisterButtonImage(): string
    {
        return $this->registerButtonImage;
    }

    /**
     * @return \Videoslots\User\ThirdPartyVerificationFields\DataTransferObject\StartExternalVerificationButtonData
     */
    public function getStartExternalVerificationButtonData(): StartExternalVerificationButtonData
    {
        return $this->startExternalVerificationButtonData;
    }
}
