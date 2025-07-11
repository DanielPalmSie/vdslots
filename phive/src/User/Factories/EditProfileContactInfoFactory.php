<?php

declare(strict_types=1);

namespace Videoslots\User\Factories;

use DBUser;
use FormerCommon;
use Laraphive\Domain\User\DataTransferObjects\EditProfile\FormElements\ButtonData;
use Laraphive\Domain\User\DataTransferObjects\EditProfile\FormElements\InputData;
use Laraphive\Domain\User\DataTransferObjects\EditProfile\FormElements\OptionData;
use Laraphive\Domain\User\DataTransferObjects\EditProfile\FormElements\SelectBoxData;
use Laraphive\Domain\User\DataTransferObjects\UserContactData;

class EditProfileContactInfoFactory
{
    /**
     * @param DBUser $user
     * @param UserContactData $data
     * @param bool $isApi
     *
     * @return array
     */
    public function createContactInfo(DBUser $user, UserContactData $data, bool $isApi): array
    {
        $canEditPhoneAndEmail = phive()->isEmpty($user->hasSetting('change-cinfo-unlock-date')) ||
            p('change.contact.info');
        $canEditLocation = phive()->isEmpty($user->hasSetting('change-address-unlock-date')) ||
            p('change.contact.info');

        $provinces = lic('getProvinces', [true], $user);
        $showExtraFields = !!licSetting('show_user_extra_fields_in_admin', $user->userId)['street_building_info'];
        $hasEditAllPermission = p("users.editall") && p('change.contact.info');
        $fc = new FormerCommon();

        return [
            'name' => 'contact_info',
            'headline' => 'register.contact.info',
            'form_elements' => [
                new InputData(
                    'register.email',
                    'email',
                    $data->getEmail(),
                    'text',
                    $this->showField('email', true),
                    !$canEditPhoneAndEmail
                ),
                new InputData(
                    'register.mobile',
                    'mobile',
                    $data->getMobile(),
                    'tel',
                    $this->showField('mobile', true),
                    !$canEditPhoneAndEmail
                ),
                new InputData(
                    'register.city',
                    'city',
                    $data->getCity(),
                    'text',
                    $this->showField('city', true),
                    !$canEditLocation
                ),
                new SelectBoxData(
                    'register.province',
                    'main_province',
                    $data->getProvince() ?? '',
                    new OptionData($provinces !== false ? $provinces : []),
                    '',
                    $this->showField('main_province', !!licSetting('require_main_province', $user)),
                    !empty(lic('getForcedProvince', [], $user)) || !$canEditLocation,
                ),
                new InputData(
                    'register.zipcode',
                    'zipcode',
                    $data->getZipcode(),
                    'text',
                    $this->showField('zipcode', true),
                    !$canEditLocation
                ),
                new InputData(
                    'register.address',
                    'address',
                    $data->getAddress(),
                    'text',
                    $this->showField('address', !$showExtraFields),
                    !$canEditLocation
                ),
                new SelectBoxData(
                    'account.dob',
                    'birthdate',
                    $this->getDateOfBirthData('birthdate'),
                    new OptionData($fc->getDays()),
                    'day',
                    $this->showField('birthdate', $user->getAttr('dob') === '0000-00-00'),
                    lic('shouldDisableInput', ['birthdate']),
                ),
                new SelectBoxData(
                    'account.dob',
                    'birthmonth',
                    $this->getDateOfBirthData('birthmonth'),
                    new OptionData($fc->getFullMonths()),
                    'month',
                    $this->showField('birthmonth', $user->getAttr('dob') === '0000-00-00'),
                    lic('shouldDisableInput', ['birthmonth']),
                ),
                new SelectBoxData(
                    'account.dob',
                    'birthyear',
                    $this->getDateOfBirthData('birthyear'),
                    new OptionData($fc->getYears($this->getRequiredAge($user))),
                    'year',
                    $this->showField('birthyear', $user->getAttr('dob') === '0000-00-00'),
                    lic('shouldDisableInput', ['birthyear']),
                ),
                new InputData(
                    'register.street.nostar',
                    'address',
                    $data->getAddress(),
                    'text',
                    $this->showField('address', !!$showExtraFields),
                    !$canEditLocation
                ),
                new InputData(
                    'register.building.nostar',
                    'building',
                    $data->getBuilding() ?? '',
                    'text',
                    $this->showField('building', !!$showExtraFields),
                    !$canEditLocation
                ),
                new InputData(
                    'account.dob',
                    'dob',
                    $data->getBirthdate(),
                    'date',
                    $this->showField('dob', $hasEditAllPermission && $user->getAttr('dob') !== '0000-00-00'),
                ),
                new InputData(
                    'profile.firstname',
                    'firstname',
                    $data->getFirstName(),
                    'text',
                    $this->showField('firstname', $hasEditAllPermission)
                ),
                new InputData(
                    'profile.surname',
                    'lastname',
                    $data->getLastName(),
                    'text',
                    $this->showField('lastname', $hasEditAllPermission),
                ),
            ],
            'descriptions' => [
                $canEditPhoneAndEmail && $canEditLocation ? 'change.30day.limit.html' : 'edit-profile.already-changed-details',
            ],
            'buttons' => [
                new ButtonData(
                    'submit',
                    'validate_contact_info',
                    'register.update'
                ),
                new ButtonData(
                    'submit',
                    'submit_contact_info',
                    'register.update'
                ),
            ],
        ];
    }

    private function showField($field, $expression): bool
    {
        if (lic('shouldHideInput', [$field])) {
            return false;
        }

        return $expression;
    }

    /**
     * @param string $name
     *
     * @return mixed|string
     */
    private function getDateOfBirthData(string $name)
    {
        $defaultValue = '';

        if ($name === 'birthyear') {
            $defaultValue = '1970';
        }

        return ! empty($_POST[$name]) ? $_POST[$name] : $defaultValue;
    }

    /**
     * @param DBUser $user
     *
     * @return mixed
     */
    private function getRequiredAge(DBUser $user)
    {
        $data = $user->getData();

        return phive('SQL')
            ->getValue("SELECT reg_age FROM bank_countries WHERE iso = '{$data['country']}'");
    }
}
