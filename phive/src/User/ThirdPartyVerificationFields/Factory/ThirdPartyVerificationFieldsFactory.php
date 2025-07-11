<?php

declare(strict_types=1);

namespace Videoslots\User\ThirdPartyVerificationFields\Factory;

use RegistrationHtml;
use Videoslots\User\ThirdPartyVerificationFields\DataTransferObject\ThirdPartyVerificationFieldsData;

require_once __DIR__ . "/../../../../modules/DBUserHandler/Registration/RegistrationHtml.php";

final class ThirdPartyVerificationFieldsFactory
{
    /**
     * @var string
     */
    private const IMAGE_PATH = "diamondbet/images/license/%s";

    /**
     * @var string
     */
    private const VERIFICATION_IMAGE_NAME = 'verification-logo.png';

    /**
     * @var string
     */
    private string $context;

    /**
     * @var string
     */
    private string $country;

    /**
     * @param string $context
     * @param string $box_id
     * @param null|mixed $country
     *
     * @return \Videoslots\User\ThirdPartyVerificationFields\DataTransferObject\ThirdPartyVerificationFieldsData
     */
    public function getFields(string $context, string $box_id, $country = null): ThirdPartyVerificationFieldsData
    {
        $this->country = $country ?? licJur();
        $this->context = $context;

        $header = $this->getHeader();
        $topPartData = (new TopPartFactory())->create($box_id, $header);

        $customLoginInfo = lic('customLoginInfo', [$context, true], null, null, $country) ?? "";
        $customLoginInfo = ($customLoginInfo == false) ? "" : $customLoginInfo;
        $nidPlaceholder = lic('getNidPlaceholder', [], null, null, $country) ?? "";
        $nidPlaceholder = ($nidPlaceholder == false) ? "" : $nidPlaceholder;
        $personalNumberMessage = $this->getPersonalNumberMessage();
        $rememberNidMessage = 'bankid.login.remember';

        $startVerificationButton = (new StartExternalVerificationButtonFactory())->create($context, $country);

        $registerButtonImage = $this->getRegisterButtonImage();

        return new ThirdPartyVerificationFieldsData(
            $topPartData,
            $customLoginInfo,
            $nidPlaceholder,
            $personalNumberMessage,
            $rememberNidMessage,
            $registerButtonImage,
            $startVerificationButton
        );
    }

    private function getRegisterButtonImage(): string
    {
        $verificationImagePath = sprintf(
            self::IMAGE_PATH . "/" . self::VERIFICATION_IMAGE_NAME,
            $this->country
        );

        $verificationImageFullPath = __DIR__ . "/../../../../../" . $verificationImagePath;

        if (! file_exists($verificationImageFullPath)) {
            return "";
        }

        return $verificationImagePath;
    }

    /**
     * @return string
     */
    private function getPersonalNumberMessage(): string
    {
        $personalNumberMessage = lic('personalNumberMessage', [false]);
        if (empty($personalNumberMessage)) {
            $personalNumberMessage = "register.personal_number.error.message";
        }

        return $personalNumberMessage;
    }

    /**
     * @return string
     */
    private function getHeader(): string
    {
        $header = getLoginHeaderFromContext($this->context);
        if ($this->context == RegistrationHtml::CONTEXT_REGISTRATION_MITID) {
            $header = 'verify.with.nid.mitid.' . phive('Licensed')->getLicCountry();
        }

        return $header;
    }
}
