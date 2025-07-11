<?php

declare(strict_types=1);

namespace Videoslots\User\LoginFields\Formatter;

use Videoslots\User\CustomLoginTop\CustomLoginTop;
use Videoslots\User\LoginFields\DataTransferObject\LoginDefault\ForgotPasswordData;
use Videoslots\User\LoginFields\DataTransferObject\LoginDefault\LoginCaptchaData;
use Videoslots\User\LoginFields\DataTransferObject\LoginDefault\PasswordFieldData;
use Videoslots\User\LoginFields\DataTransferObject\LoginDefault\SubmitFieldData;
use Videoslots\User\LoginFields\DataTransferObject\LoginDefault\UsernameFieldData;
use Videoslots\User\LoginFields\DataTransferObject\LoginDefaultData;

final class LoginDefaultFormatter
{
    /**
     * @param \Videoslots\User\LoginFields\DataTransferObject\LoginDefaultData $data
     *
     * @return array
     */
    public function format(LoginDefaultData $data): array
    {
        return [
            'custom-login-top' => $this->formatCustomLoginTop($data->getCustomLoginTop()),
            'username' => $this->formatUsernameField($data->getUsernameFieldData()),
            'password' => $this->formatPasswordField($data->getPasswordFieldData()),
            'captcha' => $this->formatLoginCaptchaField($data->getLoginCaptchaData()),
            'submit' => $this->formatSubmitField($data->getSubmitFieldData()),
            'forgot_password' => $this->formatForgotPasswordField($data->getForgotPasswordData()),
        ];
    }

    /**
     * @param \Videoslots\User\LoginFields\DataTransferObject\LoginDefault\UsernameFieldData $data
     *
     * @return array
     */
    private function formatUsernameField(UsernameFieldData $data): array
    {
        return [
            'type' => $data->getType(),
            'input_type' => $data->getInputType(),
            'name' => $data->getName(),
            'placeholder' => $data->getPlaceholder(),
        ];
    }

    /**
     * @param \Videoslots\User\LoginFields\DataTransferObject\LoginDefault\PasswordFieldData $data
     *
     * @return array
     */
    private function formatPasswordField(PasswordFieldData $data): array
    {
        return [
            'type' => $data->getType(),
            'input_type' => $data->getInputType(),
            'name' => $data->getName(),
            'placeholder' => $data->getPlaceholder(),
        ];
    }

    /**
     * @param \Videoslots\User\LoginFields\DataTransferObject\LoginDefault\SubmitFieldData $data
     *
     * @return array
     */
    private function formatSubmitField(SubmitFieldData $data): array
    {
        return [
            'type' => $data->getType(),
            'button_type' => $data->getButtonType(),
            'alias' => $data->getAlias(),
            'disabled' => $data->isDisabled(),
        ];
    }

    /**
     * @param \Videoslots\User\LoginFields\DataTransferObject\LoginDefault\ForgotPasswordData $data
     *
     * @return array
     */
    private function formatForgotPasswordField(ForgotPasswordData $data): array
    {
        return [
            'type' => $data->getType(),
            'url' => $data->getUrl(),
            'alias' => $data->getAlias(),
        ];
    }

    /**
     * @param \Videoslots\User\LoginFields\DataTransferObject\LoginDefault\LoginCaptchaData $data
     *
     * @return array
     */
    private function formatLoginCaptchaField(LoginCaptchaData $data): array
    {
        return [
            'type' => $data->getType(),
            'input_type' => $data->getInputType(),
            'image' => $data->getImage(),
            'alias' => $data->getAlias(),
            'visible' => $data->isVisible(),
        ];
    }

    /**
     * @param \Videoslots\User\CustomLoginTop\CustomLoginTop $data
     *
     * @return array
     */
    private function formatCustomLoginTop(CustomLoginTop $data): array
    {
        return $data->toArray();
    }
}
