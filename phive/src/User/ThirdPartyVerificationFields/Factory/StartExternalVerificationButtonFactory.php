<?php

declare(strict_types=1);

namespace Videoslots\User\ThirdPartyVerificationFields\Factory;

use RegistrationHtml;
use Videoslots\User\ThirdPartyVerificationFields\DataTransferObject\StartExternalVerificationButtonData;

final class StartExternalVerificationButtonFactory
{
    /**
     * @param string $context
     * @param null|mixed $country
     *
     * @return \Videoslots\User\ThirdPartyVerificationFields\DataTransferObject\StartExternalVerificationButtonData
     */
    public function create(string $context, $country = null): StartExternalVerificationButtonData
    {
        $image = "";
        $disabled = false;
        $disabledText = "";

        if ($context === RegistrationHtml::CONTEXT_REGISTRATION_MITID) {
            $alias = "dk.register.with.mitid";
            $disabled = licSetting('mit_id_disabled') === true;
            $image = lic('imgUri', ['dk-mitid.png']) ?? "";
            $disabledText = "mitid.currently.unavailable";
        } else {
            $alias = lic('getSuffixContext', ["$context.with.verification.method", false], null, null, $country) ?? "";
            $alias = ($alias == false) ? "" : $alias;
        }

        return new StartExternalVerificationButtonData(
            $alias,
            $image,
            $disabled,
            $disabledText
        );
    }
}
