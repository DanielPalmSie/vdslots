<?php

declare(strict_types=1);

namespace Videoslots\User\Services;

use Laraphive\Support\Settings\RedirectActionSettings;

class LoginRedirectsValidatorService
{
    /**
     * @param string $action
     * @param \DBUser $user
     *
     * @return bool
     */
    public function validate(string $action, \DBUser $user): bool
    {
        switch ($action) {
            case RedirectActionSettings::RG_DEPOSIT:
                $result = $this->validateRgDeposit();

                break;
            case RedirectActionSettings::INTENDED_GAMBLING:
                $result = $this->validateIntendedGambling();

                break;
            case RedirectActionSettings::NEW_PASSWORD_PAGE:
                $result = $this->validatePasswordChanged($action, $user);

                break;
            case RedirectActionSettings::FIRST_DEPOSIT:
                $result = $this->validateFirstDeposit($user);

                break;
            case RedirectActionSettings::RG_INFO:
                $result = $this->validateRgInfo($user);

                break;
            default:
                $result = false;
        }

        return $result;
    }

    /**
     * @return bool
     */
    private function validateIntendedGambling(): bool
    {
        return lic('doIntendedGambling');
    }

    /**
     * @return false
     */
    private function validateRgDeposit(): bool
    {
        return lic('hasDepositLimitOnCashier');
    }

    /**
     * @param string $action
     * @param \DBUser $user
     *
     * @return bool
     */
    private function validatePasswordChanged(string $action, \DBUser $user): bool
    {
        return $user->getSetting($action) === 'yes';
    }

    /**
     * @param \DBUser $user
     *
     * @return bool
     */
    private function validateFirstDeposit(\DBUser $user): bool
    {
        return phive('UserHandler')->doForceDeposit($user);
    }

    /**
     * @param \DBUser $user
     *
     * @return bool
     */
    private function validateRgInfo(\DBUser $user): bool
    {
        return ! empty(licSetting(RedirectActionSettings::RG_INFO, $user));
    }
}
