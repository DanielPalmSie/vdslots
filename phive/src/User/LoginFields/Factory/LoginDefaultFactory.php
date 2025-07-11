<?php

declare(strict_types=1);

namespace Videoslots\User\LoginFields\Factory;

use Videoslots\User\CustomLoginTop\CustomLoginTop;
use Videoslots\User\CustomLoginTop\Factory\CustomLoginTopFactory;
use Videoslots\User\LoginFields\DataTransferObject\LoginDefault\ForgotPasswordData;
use Videoslots\User\LoginFields\DataTransferObject\LoginDefault\LoginCaptchaData;
use Videoslots\User\LoginFields\DataTransferObject\LoginDefault\PasswordFieldData;
use Videoslots\User\LoginFields\DataTransferObject\LoginDefault\SubmitFieldData;
use Videoslots\User\LoginFields\DataTransferObject\LoginDefault\UsernameFieldData;
use Videoslots\User\LoginFields\DataTransferObject\LoginDefaultData;

final class LoginDefaultFactory
{
    /**
     * @var bool
     */
    private bool $isMaintenanceEnabled;

    /**
     * @var string
     */
    private string $context;

    /**
     * @param bool $isMaintenanceEnabled
     * @param string $context
     *
     * @return void
     */
    public function create(bool $isMaintenanceEnabled, string $context): LoginDefaultData
    {
        $this->isMaintenanceEnabled = $isMaintenanceEnabled;
        $this->context = $context;

        $email = $this->createUsernameFieldData();
        $password = $this->createPasswordFieldData();
        $submit = $this->createSubmitField();
        $forgotPassword = $this->createForgotPasswordData();
        $loginCaptcha = $this->createLoginCaptchaData();
        $customLoginTop = $this->createCustomLoginTop();
        $customLoginInfo = $this->getCustomLoginInfo();

        return new LoginDefaultData(
            $customLoginTop,
            $customLoginInfo,
            $email,
            $password,
            $submit,
            $forgotPassword,
            $loginCaptcha
        );
    }

    /**
     * @return \Videoslots\User\LoginFields\DataTransferObject\LoginDefault\UsernameFieldData
     */
    private function createUsernameFieldData(): UsernameFieldData
    {
        return new UsernameFieldData(
            'username',
            'input',
            'email',
            phive('UserHandler')->getLoginFirstInput(false)
        );
    }

    /**
     * @return \Videoslots\User\LoginFields\DataTransferObject\LoginDefault\PasswordFieldData
     */
    private function createPasswordFieldData(): PasswordFieldData
    {
        return new PasswordFieldData(
            'input',
            'password',
            'password',
            'registration.password',
        );
    }

    /**
     * @return \Videoslots\User\LoginFields\DataTransferObject\LoginDefault\SubmitFieldData
     */
    public function createSubmitField(): SubmitFieldData
    {
        $submitText = licSetting('login_button_text') ?? 'login';

        return new SubmitFieldData(
            'button',
            'submit',
            $submitText,
            $this->isMaintenanceEnabled || licSetting('extra_check_onLogin'),
        );
    }

    /**
     * @return \Videoslots\User\LoginFields\DataTransferObject\LoginDefault\ForgotPasswordData
     */
    private function createForgotPasswordData(): ForgotPasswordData
    {
        return new ForgotPasswordData(
            'link',
            llink('forgot-password'),
            'forgot-password'
        );
    }

    /**
     * @return \Videoslots\User\LoginFields\DataTransferObject\LoginDefault\LoginCaptchaData
     */
    private function createLoginCaptchaData(): LoginCaptchaData
    {
        return new LoginCaptchaData(
            'input',
            'text',
            '',
            'captcha.code',
            false
        );
    }

    /**
     * @return \Videoslots\User\CustomLoginTop\CustomLoginTop
     */
    private function createCustomLoginTop(): CustomLoginTop
    {
        return (new CustomLoginTopFactory())->create($this->context);
    }

    /**
     * @return string
     */
    private function getCustomLoginInfo(): string
    {
        $result = lic('customLoginInfo', [$this->context, true]);

        return ! is_string($result) ? "" : $result;
    }
}
