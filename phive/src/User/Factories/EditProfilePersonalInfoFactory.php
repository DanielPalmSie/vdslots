<?php

declare(strict_types=1);

namespace Videoslots\User\Factories;

use Laraphive\Domain\User\DataTransferObjects\EditProfile\FormElements\ButtonData;
use Laraphive\Domain\User\DataTransferObjects\EditProfile\FormElements\CheckboxData;
use Laraphive\Domain\User\DataTransferObjects\EditProfile\FormElements\OptionData;
use Laraphive\Domain\User\DataTransferObjects\EditProfile\FormElements\SelectBoxData;

class EditProfilePersonalInfoFactory
{
    /**
     * @param \DBUser $user
     * @param array $languageList
     * @param array $currencies
     * @param bool $isApi
     *
     * @return array
     */
    public function createPersonalInfo(\DBUser $user, array $languageList, array $currencies, bool $isApi): array
    {
        $forcedLanguage = lic('getForcedLanguage', [], $user);
        $acceptedTac = $this->getIsAcceptedTacChecked($user);
        $acceptedBonusTac = $this->getIsAcceptedBonusTacChecked($user);

        return [
            'name' => 'personal_info',
            'headline' => 'register.personalinfo',
            'form_elements' => [
                new SelectBoxData(
                    'register.chooselang',
                    'preferred_lang',
                    $user->getLang(),
                    new OptionData($languageList),
                    '',
                    $isApi || empty($forcedLanguage),
                    ! empty($forcedLanguage)
                ),
                new SelectBoxData(
                    'register.currency.nostar',
                    'currency',
                    $user->getCurrency(),
                    new OptionData($currencies),
                    '',
                    $isApi,
                    true,
                ),
                new CheckboxData(
                    'register.calls.label',
                    'calls',
                    $this->checkboxIsChecked($user, 'calls')
                ),
                new CheckboxData(
                    'show.in.events.label',
                    'show_in_events',
                    $this->checkboxIsChecked($user, 'show_in_events')
                ),
                new CheckboxData(
                    'show.notifications.label',
                    'show_notifications',
                    $this->checkboxIsChecked($user, 'show_notifications')
                ),
                new CheckboxData(
                    'realtime.updates',
                    'realtime_updates',
                    $this->checkboxIsChecked($user, 'realtime_updates', 'not-checked'),
                    (phive('Race')->countryIsExcluded($user)) ? $isApi : true
                ),
                new CheckboxData(
                    'accept.tac.label.html',
                    'accept_tac',
                    false,
                    ($acceptedTac) ? $isApi : false
                ),
                new CheckboxData(
                    'accept.bonus_tac.label.html',
                    'accept.bonus_tac',
                    false,
                    ($acceptedBonusTac) ? $isApi : false
                ),
            ],
            'descriptions' => [
                'register.supportinfo.html',
            ],
            'buttons' => [
                new ButtonData(
                    'submit',
                    'submit_accinfo',
                    'register.update'
                ),
            ],
        ];
    }

    /**
     * @param \DBUser $user
     * @param string $setting
     * @param string $default
     *
     * @return bool
     */
    private function checkboxIsChecked(\DBUser $user, string $setting, string $default = 'checked'): bool
    {
        if (! $user->hasSetting($setting) && $default === 'not-checked') {
            return false;
        }

        return (int) $user->getSetting($setting) === 1;
    }

    /**
     * @param \DBUser $user
     *
     * @return bool
     */
    private function getIsAcceptedTacChecked(\DBUser $user): bool
    {
        return ! $user->hasCurTc() || $user->hasSetting('tac_block') ||
            $user->hasSetting('tac_block_sports') ||
            (lic('isSportsbookEnabled') && ! $user->hasCurTcSports());
    }

    /**
     * @param \DBUser $user
     *
     * @return bool
     */
    private function getIsAcceptedBonusTacChecked(\DBUser $user): bool
    {
        return lic('hasBonusTermsConditions') &&
            (! $user->hasCurBtc() || $user->hasSetting('bonus_tac_block'));
    }
}
