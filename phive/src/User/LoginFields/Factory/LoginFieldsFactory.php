<?php

declare(strict_types=1);

namespace Videoslots\User\LoginFields\Factory;

use Videoslots\User\CustomLoginTop\Factory\CustomLoginTopFactory;
use Videoslots\User\LoginFields\DataTransferObject\LoginFieldsData;
use Videoslots\User\RgData\Factory\RgLogoFactory;
use Videoslots\User\ThirdPartyVerificationFields\DataTransferObject\ThirdPartyVerificationFieldsData;
use Videoslots\User\ThirdPartyVerificationFields\Factory\ThirdPartyVerificationFieldsFactory;
use Videoslots\User\ThirdPartyVerificationFields\Factory\TopPartFactory;

final class LoginFieldsFactory
{
    /**
     * @var string
     */
    private string $context;

    /**
     * @var string
     */
    private string $boxId;

    /**
     * @param string $boxId
     * @param string $context
     *
     * @return \Videoslots\User\LoginFields\DataTransferObject\LoginFieldsData
     */
    public function getFields(string $boxId, string $context): LoginFieldsData
    {
        $this->context = $context;
        $this->boxId = $boxId;

        $header = $this->getHeader();

        $topPartData = (new TopPartFactory())->create($boxId, $header);

        $rgLogo = (new RgLogoFactory())->create();

        $hasCustomLoginTopFields = (new CustomLoginTopFactory())->hasCustomLoginTop($context);

        $maintenanceData = (new MaintenanceFactory())->create();

        $defaultLoginFields = (new LoginDefaultFactory())->create($maintenanceData->isEnabled(), $context);

        $thirdPartyVerificationFieldsData = $this->getThirdPartyVerificationFieldsData();

        return new LoginFieldsData(
            $topPartData,
            $hasCustomLoginTopFields,
            $rgLogo,
            $defaultLoginFields,
            $maintenanceData,
            $thirdPartyVerificationFieldsData
        );
    }

    /**
     * @return string
     */
    private function getHeader(): string
    {
        return getLoginHeaderFromContext($this->context);
    }

    /**
     * @return bool
     */
    private function hasThirdPartyVerification(): bool
    {
        $country = licJur();

        return lic('methodExists', ['customLoginBottom']) && $country == 'SE';
    }

    /**
     * @return \Videoslots\User\ThirdPartyVerificationFields\DataTransferObject\ThirdPartyVerificationFieldsData|null
     */
    private function getThirdPartyVerificationFieldsData(): ?ThirdPartyVerificationFieldsData
    {
        if (! $this->hasThirdPartyVerification()) {
            return null;
        }

        return (new ThirdPartyVerificationFieldsFactory())->getFields($this->context, $this->boxId);
    }
}
