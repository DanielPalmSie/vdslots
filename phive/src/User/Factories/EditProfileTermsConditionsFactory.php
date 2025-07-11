<?php

declare(strict_types=1);

namespace Videoslots\User\Factories;

use Laraphive\Domain\User\DataTransferObjects\EditProfile\FormElements\ButtonData;

class EditProfileTermsConditionsFactory
{
    /**
     * @return array
     */
    public function createTermsConditions(): array
    {
        return [
            'name' => 'terms_conditions',
            'headline' => 'edit-profile.terms-and-conditions.title',
            'buttons' => [
                new ButtonData(
                    'submit',
                    'submit_terms_and_conditions',
                    'edit-profile.terms-and-conditions.btn-text'
                ),
            ],
        ];
    }
}
