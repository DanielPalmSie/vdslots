<?php

declare(strict_types=1);

namespace Videoslots\User\LoginFields\DataTransferObject;

use Videoslots\User\RgData\RgLogo;
use Videoslots\User\ThirdPartyVerificationFields\DataTransferObject\ThirdPartyVerificationFieldsData;
use Videoslots\User\ThirdPartyVerificationFields\DataTransferObject\TopPartData;

final class LoginFieldsData
{
    /**
     * @var \Videoslots\User\ThirdPartyVerificationFields\DataTransferObject\TopPartData
     */
    private TopPartData $topPartData;

    /**
     * @var \Videoslots\User\RgData\RgLogo
     */
    private RgLogo $rgLogo;

    /**
     * @var \Videoslots\User\LoginFields\DataTransferObject\LoginDefaultData
     */
    private LoginDefaultData $loginDefaultData;

    /**
     * @var \Videoslots\User\LoginFields\DataTransferObject\MaintenanceData
     */
    private MaintenanceData $maintenanceData;

    /**
     * @var bool
     */
    private bool $hasCustomLoginTopFields;

    /**
     * @var \Videoslots\User\ThirdPartyVerificationFields\DataTransferObject\ThirdPartyVerificationFieldsData|null
     */
    private ?ThirdPartyVerificationFieldsData $thirdPartyVerificationFieldsData;

    /**
     * @param \Videoslots\User\ThirdPartyVerificationFields\DataTransferObject\TopPartData $topPartData
     * @param \Videoslots\User\RgData\RgLogo $rgLogo
     * @param LoginDefaultData $loginDefaultData
     * @param MaintenanceData $maintenanceData
     * @param bool $hasCustomLoginTopFields
     * @param ?ThirdPartyVerificationFieldsData $thirdPartyVerificationFieldsData
     */
    public function __construct(
        TopPartData $topPartData,
        bool $hasCustomLoginTopFields,
        RgLogo $rgLogo,
        LoginDefaultData $loginDefaultData,
        MaintenanceData $maintenanceData,
        ?ThirdPartyVerificationFieldsData $thirdPartyVerificationFieldsData
    ) {
        $this->topPartData = $topPartData;
        $this->rgLogo = $rgLogo;
        $this->loginDefaultData = $loginDefaultData;
        $this->maintenanceData = $maintenanceData;
        $this->hasCustomLoginTopFields = $hasCustomLoginTopFields;
        $this->thirdPartyVerificationFieldsData = $thirdPartyVerificationFieldsData;
    }

    /**
     * @return \Videoslots\User\ThirdPartyVerificationFields\DataTransferObject\TopPartData
     */
    public function getTopPartData(): TopPartData
    {
        return $this->topPartData;
    }

    /**
     * @return bool
     */
    public function hasCustomLoginTopFields(): bool
    {
        return $this->hasCustomLoginTopFields;
    }

    /**
     * @return \Videoslots\User\RgData\RgLogo
     */
    public function getRgLogo(): RgLogo
    {
        return $this->rgLogo;
    }

    /**
     * @return \Videoslots\User\LoginFields\DataTransferObject\LoginDefaultData
     */
    public function getLoginDefaultData(): LoginDefaultData
    {
        return $this->loginDefaultData;
    }

    /**
     * @return \Videoslots\User\LoginFields\DataTransferObject\MaintenanceData
     */
    public function getMaintenanceData(): MaintenanceData
    {
        return $this->maintenanceData;
    }

    /**
     * @return \Videoslots\User\ThirdPartyVerificationFields\DataTransferObject\ThirdPartyVerificationFieldsData|null
     */
    public function getThirdPartyVerificationFieldsData(): ?ThirdPartyVerificationFieldsData
    {
        return $this->thirdPartyVerificationFieldsData;
    }
}
