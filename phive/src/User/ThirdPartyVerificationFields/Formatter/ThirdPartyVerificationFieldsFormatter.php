<?php

declare(strict_types=1);

namespace Videoslots\User\ThirdPartyVerificationFields\Formatter;

use Videoslots\User\ThirdPartyVerificationFields\DataTransferObject\ThirdPartyVerificationFieldsData;

final class ThirdPartyVerificationFieldsFormatter
{
    /**
     * @param \Videoslots\User\ThirdPartyVerificationFields\DataTransferObject\ThirdPartyVerificationFieldsData $data
     *
     * @return array
     */
    public function format(ThirdPartyVerificationFieldsData $data): array
    {
        return [
            'box_headline_alias' => $data->getTopPartData()->getBoxHeadlineAlias(),
            'custom_login_info' => $data->getCustomLoginInfo(),
            'nid_input' => [
                'type' => 'input',
                'input_type' => 'number',
                'name' => 'nid',
                'placeholder' => $data->getNidPlaceholder(),
            ],
            'remember_me' => [
                'type' => 'checkbox',
                'alias' => $data->getRememberNidMessage(),
            ],
            'start_verification' => [
                'type' => 'submit',
                'alias' => $data->getStartExternalVerificationButtonData()->getAlias(),
                'image' => $data->getRegisterButtonImage(),
            ],
            'personal_number_error_message' => $data->getPersonalNumberMessage(),
        ];
    }
}
