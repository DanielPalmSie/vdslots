<?php

namespace App\Repositories;

use App\Classes\LegacyDeposits;
use App\Classes\Mts;
use App\Classes\Settings;
use App\Helpers\DataFormatHelper;
use App\Models\BankCountry;
use App\Models\User;
use App\Models\UserSetting;
use Carbon\Carbon;
use Silex\Application;

class UserSettingsRepository
{
    /** @var User $user */
    protected $user;

    /** @var  Settings $settings */
    public $settings;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function populateSettings()
    {
        $settings = UserSetting::sh($this->user->getKey())
            ->select('setting', 'value')
            ->where('user_id', $this->user->getKey())
            ->get();

        $this->settings = new Settings($settings);
    }

    public function getSettings()
    {
        return $this->settings;
    }

    public function updateAll(Application $app, $new_settings)
    {
        if ($new_settings->form_id == 'settings') {
            $new_settings->newsletter = is_null($new_settings->newsletter) ? 0 : $new_settings->newsletter;
            if ($new_settings->newsletter != $this->user->newsletter) {
                $this->user->update(['newsletter' => $new_settings->newsletter]);
            }
            foreach (['sms_on_login', 'calls', 'show_in_events', 'show_notifications', 'realtime_updates', 'segment'] as $setting) {
                $this->updateCandidate($new_settings, $setting);
            }
            $new_settings->sms = is_null($new_settings->sms) ? 0 : $new_settings->sms;
            if ($this->settings->sms !== $new_settings->sms) {
                $this->user->repo->setSetting('sms', $new_settings->sms);
            }
        } elseif ($new_settings->form_id == 'payment-information' && p('user.inout.defaults')) {
            foreach (['mb_email', 'net_account', 'paypal_email', 'btc_address','paypal_payer_id', 'muchbetter_mobile'] as $setting) {
                $this->updateCandidate($new_settings, $setting);
            }

            if (!empty($new_settings->muchbetter_mobile)) {
                $this->externalMuchBetterUpdate($app, $new_settings->muchbetter_mobile);
            }
        } elseif ($new_settings->form_id == 'other-settings' && p('user.casino.settings')) {
            foreach (['sub_aff_no_neg_carry', 'casino_loyalty_percent', 'bonus_block', 'max_thirty_withdrawal',
                         'permanent_dep_lim', 'permanent_dep_period', 'free_deposits', 'free_withdrawals', 'withdraw_period',
                         'withdraw_period_times', 'lock-date', 'affiliate_admin_fee', 'dep-limit-playblock'] as $setting) {
                if (p("user.casino.settings.$setting")) {
                    $this->updateCandidate($new_settings, $setting);
                }
            }
        } elseif ($new_settings->form_id == 'popular-forums' && p('edit.forums')) {
            foreach (DataFormatHelper::getPopularForums() as $key => $forum) {
                $candidate = "forum-username-$key";
                $this->updateCandidate($new_settings, $candidate);
            }
        } elseif ($new_settings->form_id == 'deposits-methods' && p('user.edit.deposits.methods')) {
            foreach (LegacyDeposits::getUserDepositMethods($this->user) as $setting) {
                $candidate = "disable-{$setting}";
                if (empty($new_settings->{$candidate})) {
                    $this->user->repo->setSetting($candidate, 1);
                } else {
                    $this->user->repo->deleteSetting($candidate);
                }
            }
        } elseif ($new_settings->form_id == 'follow-up-settings' && p('user.edit.follow.up')) {
            foreach (DataFormatHelper::getFollowUpOptions() as $key => $text) {
                $candidate = "{$new_settings->category}-{$key}-follow-up";
                $this->updateCandidate($new_settings, $candidate);
            }
            $risk_group = "{$new_settings->category}-risk-group";
            $this->updateCandidate($new_settings, $risk_group);
        }
    }

    private function updateCandidate($new_settings, $candidate)
    {
        if ($this->settings->{$candidate} != $new_settings->{$candidate}) {
            if (!empty($this->settings->{$candidate}) && empty($new_settings->{$candidate})) {
                $this->user->repo->deleteSetting($candidate);
            } elseif (!empty($new_settings->{$candidate}) && empty($this->settings->{$candidate})) {
                $this->user->repo->setSetting($candidate, $new_settings->{$candidate});
            } else {
                $this->user->repo->setSetting($candidate, $new_settings->{$candidate});
            }
        }
    }

    /* Country login related functions */
    //TODO sharding
    public function getAllowedLoginCountries(User $user)
    {
        $allowed_db = $user->settings()->where('setting', 'LIKE', 'login-allowed-%')->pluck('setting');
        $all = $this->getAllBankCountries()->keyBy('iso');

        $res = [];
        foreach ($allowed_db as $allowed_iso) {
            $iso = explode('-', $allowed_iso)[2];
            $res[$iso] = $all[$iso]->printable_name;
        }

        return $res;
    }

    //TODO sharding
    public function addAllowedLoginCountry(User $user, $countryCode)
    {
        if ($user->settings()->where('setting', 'LIKE', 'login-allowed-' . $countryCode)->count() == 0) {
            $user->settings()->create(['setting' => 'login-allowed-' . $countryCode, 'value' => 1, 'created' => Carbon::now()]);
        }
    }

    //TODO sharding
    public function getDisabledDepositsMethods()
    {
        return $this->user->settings()->select('setting')->where('setting', 'LIKE', 'disable-%')
            ->get()->pluck('setting')->transform(function ($item, $key) {
                return explode('-', $item)[1];
            });
    }

    //TODO sharding
    public function deleteAllowedLoginCountry(User $user, $allowedCountry)
    {
        $user->settings()->where('setting', $allowedCountry)->delete();
    }

    //TODO sharding check if all is working
    public function getAllBankCountries()
    {
        return BankCountry::all();
    }

    public function getInOutLimits()
    {
        return array_merge($this->settings->keyLike('-in-limit'), $this->settings->keyLike('-out-limit'));
    }

    public function getFollowUpData($as_array = true)
    {
        $settings_list = $this->settings->keyLike('-follow-up');
        if ($as_array === true) {
            $res = [];
            foreach ($settings_list as $k => $v) {
                $exploded = explode('-', $k);
                $res[$exploded[0]][$exploded[1]] = $v;
            }
            return $res;
        } else {
            return $settings_list;
        }
    }

    //TODO need to check if DISCTINT works with the current shard logic
    public static function getSettingsNames()
    {
        return UserSetting::selectRaw('DISTINCT setting')
            ->whereRaw("setting NOT RLIKE '[0-9]'")
            ->whereRaw("setting NOT LIKE 'login-allowed-%'")
            ->get();
    }

    /**
     * @param $number
     * @return false
     * @throws \Exception
     */
    public function externalMuchBetterUpdate(Application $app, $number)
    {
        $updated = (new Mts($app))->updateMuchBetterNumber($this->user->id, $number);

        if (!$updated['success']) {
            return false;
        }

        $this->user->setMobile($number);
        $this->user->save([], true);
    }
}
