<?php

declare(strict_types=1);

namespace Videoslots\User\LoginFields\DataTransferObject;

use Videoslots\User\CustomLoginTop\CustomLoginTop;
use Videoslots\User\LoginFields\DataTransferObject\LoginDefault\ForgotPasswordData;
use Videoslots\User\LoginFields\DataTransferObject\LoginDefault\LoginCaptchaData;
use Videoslots\User\LoginFields\DataTransferObject\LoginDefault\PasswordFieldData;
use Videoslots\User\LoginFields\DataTransferObject\LoginDefault\SubmitFieldData;
use Videoslots\User\LoginFields\DataTransferObject\LoginDefault\UsernameFieldData;

final class LoginDefaultData
{
    /**
     * @var \Videoslots\User\LoginFields\DataTransferObject\LoginDefault\UsernameFieldData
     */
    private UsernameFieldData $usernameFieldData;

    /**
     * @var \Videoslots\User\LoginFields\DataTransferObject\LoginDefault\PasswordFieldData
     */
    private PasswordFieldData $passwordFieldData;

    /**
     * @var \Videoslots\User\LoginFields\DataTransferObject\LoginDefault\SubmitFieldData
     */
    private SubmitFieldData $submitFieldData;

    /**
     * @var \Videoslots\User\LoginFields\DataTransferObject\LoginDefault\ForgotPasswordData
     */
    private ForgotPasswordData $forgotPasswordData;

    /**
     * @var \Videoslots\User\LoginFields\DataTransferObject\LoginDefault\LoginCaptchaData
     */
    private LoginCaptchaData $loginCaptchaData;

    /**
     * @var \Videoslots\User\CustomLoginTop\CustomLoginTop
     */
    private CustomLoginTop $customLoginTop;

    /**
     * @var string
     */
    private string $customLoginInfo;

    /**
     * @param \Videoslots\User\CustomLoginTop\CustomLoginTop $customLoginTop
     * @param string $customLoginInfo
     * @param \Videoslots\User\LoginFields\DataTransferObject\LoginDefault\UsernameFieldData $usernameFieldData
     * @param \Videoslots\User\LoginFields\DataTransferObject\LoginDefault\PasswordFieldData $passwordFieldData
     * @param \Videoslots\User\LoginFields\DataTransferObject\LoginDefault\SubmitFieldData $submitFieldData
     * @param \Videoslots\User\LoginFields\DataTransferObject\LoginDefault\ForgotPasswordData $forgotPasswordData
     * @param \Videoslots\User\LoginFields\DataTransferObject\LoginDefault\LoginCaptchaData $loginCaptchaData
     */
    public function __construct(
        CustomLoginTop $customLoginTop,
        string $customLoginInfo,
        UsernameFieldData $usernameFieldData,
        PasswordFieldData $passwordFieldData,
        SubmitFieldData $submitFieldData,
        ForgotPasswordData $forgotPasswordData,
        LoginCaptchaData $loginCaptchaData
    ) {
        $this->usernameFieldData = $usernameFieldData;
        $this->passwordFieldData = $passwordFieldData;
        $this->submitFieldData = $submitFieldData;
        $this->forgotPasswordData = $forgotPasswordData;
        $this->loginCaptchaData = $loginCaptchaData;
        $this->customLoginTop = $customLoginTop;
        $this->customLoginInfo = $customLoginInfo;
    }

    /**
     * @return \Videoslots\User\CustomLoginTop\CustomLoginTop
     */
    public function getCustomLoginTop(): CustomLoginTop
    {
        return $this->customLoginTop;
    }

    /**
     * @return string
     */
    public function getCustomLoginInfo(): string
    {
        return $this->customLoginInfo;
    }

    /**
     * @return \Videoslots\User\LoginFields\DataTransferObject\LoginDefault\UsernameFieldData
     */
    public function getUsernameFieldData(): UsernameFieldData
    {
        return $this->usernameFieldData;
    }

    /**
     * @return \Videoslots\User\LoginFields\DataTransferObject\LoginDefault\PasswordFieldData
     */
    public function getPasswordFieldData(): PasswordFieldData
    {
        return $this->passwordFieldData;
    }

    /**
     * @return \Videoslots\User\LoginFields\DataTransferObject\LoginDefault\SubmitFieldData
     */
    public function getSubmitFieldData(): SubmitFieldData
    {
        return $this->submitFieldData;
    }

    /**
     * @return \Videoslots\User\LoginFields\DataTransferObject\LoginDefault\ForgotPasswordData
     */
    public function getForgotPasswordData(): ForgotPasswordData
    {
        return $this->forgotPasswordData;
    }

    /**
     * @return \Videoslots\User\LoginFields\DataTransferObject\LoginDefault\LoginCaptchaData
     */
    public function getLoginCaptchaData(): LoginCaptchaData
    {
        return $this->loginCaptchaData;
    }
}
