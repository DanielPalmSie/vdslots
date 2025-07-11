<?php

declare(strict_types=1);

namespace Videoslots\User\Registration;

use Laraphive\Domain\User\DataTransferObjects\RegistrationCaptchaData;
use PhiveValidator;
use RegistrationHtml;

final class RegisterStep1Service
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
    private const CAPTCHA_ACTION = 'validate_registration_captcha';

    /**
     * @var string
     */
    private string $country;

    /**
     * @param string $country
     *
     * @return array
     */
    public function getRegisterButtons(string $country): array
    {
        phive('Licensed')->skipDomainIsoOverride();
        phive('Licensed')->forceCountry($country);

        $this->country = licJur();

        $response = [];

        $response[] = $this->getRegisterButton();

        $extraButton = $this->getExtraRegisterButton();
        if (! empty($extraButton)) {
            $response[] = $extraButton;
        }

        return $response;
    }

    /**
     * @return array
     */
    private function getRegisterButton(): array
    {
        $isIntermediaryStepRequired = RegistrationHtml::intermediaryStepRequired([]);

        $action = $isIntermediaryStepRequired
            ? "START_NID_VERFICATION"
            : "START_REGISTER_STEP1";

        $response = [
            'type' => 'register',
            'alias' => lic('getRegistrationMessage', [false]),
            'action' => $action,
        ];

        $image = $this->getRegisterButtonImage();
        if (! empty($image)) {
            $response['image'] = $image;
        }

        return $response;
    }

    private function getRegisterButtonImage(): string
    {
        $verificationImagePath = sprintf(
            self::IMAGE_PATH . "/" . self::VERIFICATION_IMAGE_NAME,
            $this->country
        );

        $verificationImageFullPath = __DIR__ . "/../../../../".$verificationImagePath;

        if (! file_exists($verificationImageFullPath)) {
            return "";
        }

        return $verificationImagePath;
    }

    /**
     * @return array
     */
    private function getExtraRegisterButton(): array
    {
        $data = lic('getRegistrationSecondButton');
        if (! is_array($data)) {
            return [];
        }

        if (isset($data['image'])) {
            $data['image'] = sprintf(
                self::IMAGE_PATH . "/%s.png",
                $this->country,
                $data['image'],
            );
        }

        return $data;
    }

    /**
     * @param string $captchaCode
     * @param string|null $captchaSessionKey
     *
     * @return \Laraphive\Domain\User\DataTransferObjects\RegistrationCaptchaData|null
     */
    public function validateCaptcha(string $captchaCode, ?string $captchaSessionKey = null): ?RegistrationCaptchaData
    {
        $allowedCaptchaAttempt = phive()->getSetting('allowed_captcha_attempt', 5);
        $tooManyAttempts = limitAttempts(self::CAPTCHA_ACTION, $captchaCode, $allowedCaptchaAttempt);

        if (is_null($captchaSessionKey)) {
            $storedCode = PhiveValidator::captchaCode(true);
        } else {
            $storedCode = phMget($captchaSessionKey);
        }

        if ($tooManyAttempts || $storedCode !== $captchaCode) {
            return new RegistrationCaptchaData(
                $tooManyAttempts ? 'timeout' : 'validation',
                $tooManyAttempts ? mCluster('uaccess')->ttl(remIp() . self::CAPTCHA_ACTION) : 0,
                $tooManyAttempts ? 'registration.captcha.exceeded.attempts' : 'captcha.validation.error'
            );
        }

        if (is_null($captchaSessionKey)) {
            $_SESSION['registration_step1_captcha_validated'] = true;
        } else {
            phMset($captchaSessionKey . '_validated', true);
        }

        return null;
    }
}
