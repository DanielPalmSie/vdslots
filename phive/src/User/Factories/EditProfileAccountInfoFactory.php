<?php

declare(strict_types=1);

namespace Videoslots\User\Factories;

use Laraphive\Domain\User\DataTransferObjects\EditProfile\FormElements\ButtonData;
use Laraphive\Domain\User\DataTransferObjects\EditProfile\FormElements\InputData;
use Laraphive\Domain\User\DataTransferObjects\EditProfile\FormElements\LabelData;

class EditProfileAccountInfoFactory
{
    /**
     * @return array
     */
    public function createAccountInfo(): array
    {
        return [
            'name' => 'account_information',
            'headline' => 'register.accinfo.headline',
            'form_elements' => [
                new InputData(
                    'register.old.password',
                    'password0',
                    '',
                    'password'
                ),
                new InputData(
                    'register.password',
                    'password',
                    '',
                    'password'
                ),
                new InputData(
                    'register.password2',
                    'password2',
                    '',
                    'password'
                )
            ],
            'buttons' => [
                new ButtonData(
                    'submit',
                    'submit_password',
                    'register.update'
                ),
            ],
        ];
    }
}
