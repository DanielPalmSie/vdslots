<?php
/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2015.12.22.
 * Time: 12:45
 */

namespace App\Repositories;

use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\ReplicaFManager as ReplicaDB;
use App\Helpers\DataFormatHelper;
use App\Helpers\DownloadHelper;
use App\Helpers\GrsHelper;
use App\Models\RiskProfileRating;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class UserSearchRepository
{
    public $not_use_archived = false;

    private $permissions = [];

    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->setPermissions();
    }

    private function setPermissions()
    {
        $obfuscated_data_permission = p('user.search.show.obfuscated_data') ? true : false;

        $obfuscated_fields = [
            'affe_id',
            'alias',
            'bonus_code',
            'bust_treshold',
            'cash_balance',
            'city',
            'country',
            'cur_ip',
            'currency',
            'dob',
            'firstname',
            'lastname',
            'friend',
            'last_login',
            'last_logout',
            'newsletter',
            'preferred_lang',
            'reg_ip',
            'register_date',
            'sex',
            'verified_phone',
            'zipcode',
            'archived'
        ];

        $this->permissions = [
            'mobile' => p('users.search.mobile') ? true : false,
            'email' => p('users.search.email') ? true : false,
            'address' => p('users.search.address') ? true : false,
            'playcheck' => p('playcheck') ? true : false,
            'backend' => p('backend') ? true : false,
        ];

        foreach ($obfuscated_fields as $field) {
            $this->permissions[$field] = $obfuscated_data_permission;
        }
    }

    /**
     * @param $permission
     *
     * @return bool|null
     */
    public function getPermission($permission)
    {
        return $this->permissions[$permission];
    }

    public function getUserSearchColumnsList()
    {
        $select = [
            'active' => 'Active',
            'affe_id' => 'Affiliate id',
            'alias' => 'Alias',
            'bonus_code' => 'Bonus code',
            'bust_treshold' => 'Bust threshold',
            'cash_balance' => 'Cash balance',
            'city' => 'City',
            'country' => 'Country',
            'cur_ip' => 'Cur ip',
            'currency' => 'Currency',
            'dob' => 'DOB',
            'firstname' => 'First name',
            'lastname' => 'Last name',
            'friend' => 'Friend',
            'last_login' => 'Last login',
            'last_logout' => 'Last logout',
            'newsletter' => 'Newsletter',
            'preferred_lang' => 'Preferred language',
            'reg_ip' => 'Reg ip',
            'register_date' => 'Register date',
            'sex' => 'Sex',
            'verified_phone' => 'Verified phone',
            'zipcode' => 'Zip code'
        ];
        $columns['rest']['archived'] = 'Archived';
        if ($this->permissions['playcheck']) {
            $columns['rest']['playcheck'] = 'Playcheck';
        }
        if ($this->permissions['backend']) {
            $columns['rest']['backend'] = 'Backend';
        }

        $columns['especial'] = [
            'mobile' => 'Mobile',
            'email' => 'Email',
            'address' => 'Address'
        ];
        $columns['list'] = array_merge(['id' => 'Id', 'username' => 'Username'], $columns['especial'], $select, $columns['rest']);
        $columns['select'] = array_merge(['id' => 'Id', 'username' => 'Username'], $select);
        $columns['default_visibility'] = ['id', 'lastname', 'firstname', 'archived', 'email', 'backend'];

        return $columns;
    }

    /**
     * @param Request $request
     * @param bool    $archived
     * @param null    $users_list
     * @param boolean    $fetch_all
     *
     * @return $this|Builder
     * @throws \Exception
     */
    public function getUserSearchQuery(Request $request, $archived = false, $users_list = null, $fetch_all = false)
    {
        if ($archived) {
            $query = DB::connection('videoslots_archived')
                ->table('users AS u');
        } else {
            $query = ReplicaDB::table('users AS u', replicaDatabaseSwitcher(true));
        }

        if (!empty($users_list) && count($users_list) > 0) {
            return $query->whereIn('u.id', $users_list);
        }

        $form_elem = [];
        $extra_select = [];

        if (!empty($request->get('form'))) {
            foreach ($request->get('form') as $key => $val) {
                if (empty(key(array_values($val)[0]))) {
                    foreach ($val as $k => $v) {
                        $form_elem[$k] = $v;
                    }
                    continue;
                }
                $form_elem[key($val)][key(array_values($val)[0])] = array_values(array_values($val)[0])[0];
            }
        } else {
            $form_elem = [
                'user' => $request->get('user'),
                'province' => $request->get('province'),
                'since' => $request->get('since'),
                'before' => $request->get('before'),
                'deposit' => $request->get('deposit'),
                'withdraw' => $request->get('withdraw'),
                'settings' => $request->get('settings'),
                'blockType' => $request->get('blockType'),
                'userColumn' => $request->get('userColumn'),
                'privacySettings' => $request->get('privacySettings'),
                'other' => $request->get('other'),
                'rg_profile_rating_start' => $request->get('rg_profile_rating_start', RiskProfileRatingRepository::PROFILE_RATING_MIN_TAG),
                'rg_profile_rating_end' => $request->get('rg_profile_rating_end', RiskProfileRatingRepository::PROFILE_RATING_MAX_TAG),
                'aml_profile_rating_start' => $request->get('aml_profile_rating_start', RiskProfileRatingRepository::PROFILE_RATING_MIN_TAG),
                'aml_profile_rating_end' => $request->get('aml_profile_rating_end', RiskProfileRatingRepository::PROFILE_RATING_MAX_TAG),
            ];
        }

        $visible_columns = json_decode($_COOKIE['user-search-visible']);
        array_walk($visible_columns, function(&$column) {
            $column = substr($column,4);
        });

        foreach ($privacy_settings as $setting) {
            if(!$fetch_all && !in_array($setting, $visible_columns)) {
                continue;
            }

            $query = $query->leftJoin("users_settings as {$setting}", function ($q) use ($setting) {
                /** @var JoinClause $q */
                return $q->whereRaw("{$setting}.user_id = u.id")
                    ->where("{$setting}.setting", '=', DataFormatHelper::getSetting($setting))
                    ->whereRaw("{$setting}.value = 1");
            });
        }
        foreach ($form_elem['user'] as $key => $val) {
            if (!empty($val)) {
                $val = preg_replace('/\s+/', '', $val);
                if ($key == 'id' && is_numeric($val)) {
                    if (strpos($val, ',')) {
                        $query->whereIn('u.id', explode(',', $val));
                    } else {
                        $query->where('u.id', $val);
                    }
                } elseif ($key == 'id' && !is_numeric($val)) {
                    if (is_numeric($val)) {
                        $query->where('u.username', 'LIKE', '%' . $val . '%')
                            ->orWhere('u.id', '=', $val);
                    } else {
                        $query->where('u.username', 'LIKE', '%' . $val . '%');
                    }
                } elseif ($key == 'username') {
                    $query->where('u.username', 'LIKE', '%' . $val . '%');
                } elseif (in_array($key, ['firstname', 'lastname', 'email', 'bonus_code', 'mobile', 'alias'])) {
                    $query->where("u.$key", 'LIKE', '%' . $val . '%');
                } else {
                    if (is_array($val)) {
                        $query->whereIn("u.$key", $val);
                    } else {
                        $query->whereRaw("u.$key = '$val'");
                    }
                }
            }
        }

        foreach ($form_elem['since'] as $key => $val) {
            if (!empty($val)) {
                $query->where("u.$key", '>=', $val);
            }
        }

        if (!empty($form_elem['privacySettings'])) {
            foreach ($form_elem['privacySettings'] as $key => $val) {
                if (!empty($val)) {
                    $query->whereRaw("{$key}.value = 1");
                }
            }
        }

        foreach ($form_elem['before'] as $key => $val) {
            if (!empty($val)) {
                $query->where("u.$key", '<', $val);
            }
        }

        $deposit_amount = $form_elem['deposit']['amount'];
        if (empty($form_elem['deposit']['since']) && $deposit_amount === '0') {
            $query->leftJoin('deposits', 'deposits.user_id', '=', 'u.id')
                ->whereRaw('deposits.id IS NULL')
                ->distinct();
        }

        if (!$archived) {
            // join with deposits
            if (!empty($form_elem['deposit']['since']) && empty($deposit_amount) && $deposit_amount !== 0) { //only since date
                $this->not_use_archived = true;
                $query->leftJoin('deposits', 'deposits.user_id', '=', 'u.id')
                    ->where('deposits.timestamp', '>=', Carbon::parse($form_elem['deposit']['since'])->format('Y-m-d H:i:s'))
                    ->distinct();
            } elseif (!empty($deposit_amount) && empty($form_elem['deposit']['since'])) { //only amount
                $this->not_use_archived = true;
                $query->join(DB::raw("(SELECT
                            user_id,
                            sum(uds.deposits) AS sub_deposits_sum
                          FROM users_daily_stats uds
                          GROUP BY user_id) AS sqds"), function ($join) {
                    $join->on('u.id', '=', 'sqds.user_id');
                })
                    ->havingRaw("sub_deposits_sum >= '" . $deposit_amount. "'");
                $extra_select[] = 'sub_deposits_sum';
            } elseif (!empty($deposit_amount) && !empty($form_elem['deposit']['since'])) { //amount and since
                $this->not_use_archived = true;
                $deposit_since = Carbon::parse($form_elem['deposit']['since'])->toDateString();
                $query->join(DB::raw("(SELECT
                            user_id,
                            sum(uds.deposits) AS sub_deposits_sum
                          FROM users_daily_stats uds
                          WHERE date >= '{$deposit_since}'
                          GROUP BY user_id) AS sqds"), function ($join) {
                    $join->on('u.id', '=', 'sqds.user_id');
                })
                    ->havingRaw("sub_deposits_sum >= '" . $deposit_amount . "'");
                $extra_select[] = 'sub_deposits_sum';
            }

            //join with withdrawals
            $withdrawal_amount = $form_elem['withdraw']['amount'];

            if (!empty($form_elem['withdraw']['since']) && empty($withdrawal_amount) && $withdrawal_amount !== 0) { //only since date
                $this->not_use_archived = true;
                $query->leftJoin('pending_withdrawals', 'pending_withdrawals.user_id', '=', 'u.id')
                    ->where('pending_withdrawals.approved_at', '>=', Carbon::parse($form_elem['withdraw']['since'])->format('Y-m-d H:i:s'))
                    ->distinct();
            } elseif (!empty($withdrawal_amount) && empty($form_elem['withdraw']['since'])) { //only amount
                $this->not_use_archived = true;
                $query->join(DB::raw("(SELECT
                            user_id,
                            sum(uds.withdrawals) AS sub_withdrawals_sum
                          FROM users_daily_stats uds
                          GROUP BY user_id) AS sqws"), function ($join) {
                    $join->on('u.id', '=', 'sqws.user_id');
                })
                    ->havingRaw("sub_withdrawals_sum >= '" . $withdrawal_amount. "'");
                $extra_select[] = 'sub_withdrawals_sum';
            } elseif (!empty($withdrawal_amount) && !empty($form_elem['withdraw']['since'])) { //amount and since
                $this->not_use_archived = true;
                $withdrawal_since = Carbon::parse($form_elem['withdraw']['since'])->toDateString();
                $query->join(DB::raw("(SELECT
                            user_id,
                            sum(uds.withdrawals) AS sub_withdrawals_sum
                          FROM users_daily_stats uds
                          WHERE date >= '{$withdrawal_since}'
                          GROUP BY user_id) AS sqws"), function ($join) {
                    $join->on('u.id', '=', 'sqws.user_id');
                })
                    ->havingRaw("sub_withdrawals_sum >= '" . $withdrawal_amount . "'");
                $extra_select[] = 'sub_withdrawals_sum';
            }
        }

        //join with settings
        if (!empty($form_elem['settings']['name']) && !empty($form_elem['settings']['comparator'])) {
            $query->leftJoin('users_settings', function (JoinClause $join) use ($form_elem) {
                $join->on('users_settings.user_id', '=', 'u.id')->where('users_settings.setting', '=', $form_elem['settings']['name']);
            });
            if (empty($form_elem['settings']['value'])) {
                $query->whereRaw('users_settings.value IS NULL');
            } else {
                $query->where(function($query) use ($form_elem) {
                    $query
                        ->where('users_settings.value', $form_elem['settings']['comparator'], $form_elem['settings']['value'])
                        ->orWhere(function($query) use ($form_elem) {
                            $query
                                ->whereRaw('DATE(users_settings.value) IS NOT NULL')
                                ->whereDate('users_settings.value', $form_elem['settings']['comparator'], $form_elem['settings']['value']);
                        });
                })
                    ->whereRaw('users_settings.value IS NOT NULL');
            }
        }
        if (!empty($form_elem['other']['verified'])) { //todo check this join
            $query->leftJoin('users_settings as verified', 'verified.user_id', '=', 'u.id')
                ->where('verified.setting', 'verified');
            if ($form_elem['other']['verified'] == 'yes') {
                $query->whereRaw('verified.value = 1');
            } else {
                $query->whereRaw('verified.value <> 1');
            }
        }

        if (!empty($form_elem['blockType']['name'])){
            $query->leftJoin('users_settings', 'users_settings.user_id', '=', 'u.id')
                ->where('users_settings.setting', '=', $form_elem['blockType']['name']);
        }

        if (!empty($form_elem['other']['phone_calls'])) {  //todo check this join
            $query->leftJoin('users_settings as phone_number', 'phone_number.user_id', '=', 'u.id')
                ->where('phone_number.setting', 'calls');
            if ($form_elem['other']['phone_calls'] == 'yes') {
                $query->whereRaw('phone_number.value = 1');
            } else {
                $query->whereRaw('phone_number.value <> 1');
            }
        }

        $province = $form_elem['province'] ?? [];
        $province = in_array('ALL', $province) ? [] : $province;

        if (!empty($province)) {
            $query->leftJoin('users_settings as province', 'province.user_id', '=', 'u.id')
                ->where('province.setting', 'main_province')->whereIn('province.value', $province);
        }

        //apply AML/RG risk scores
        $rpr = RiskProfileRatingRepository::getLatestProfileRatingQuery();
        foreach ([RiskProfileRating::RG_SECTION, RiskProfileRating::AML_SECTION] as $risk_score_type){
            $type = strtolower($risk_score_type);
            $start = $form_elem[$type . '_profile_rating_start'];
            $end = $form_elem[$type . '_profile_rating_end'];

            if (!RiskProfileRating::isDefaultInterval($start, $end)) {
                $query->leftJoin(DB::raw("($rpr) AS {$type}_log"),"{$type}_log.user_id", '=', 'u.id');
                $rating_tags = GrsHelper::getRatingScoreFilterRange($this->app, $start, $end, true);
                $query->whereRaw("{$type}_log.rating_type = '$risk_score_type'");
                $query->whereRaw("{$type}_log.rating_tag IN ({$rating_tags})");
            }
        }

        //user column search
        if (!empty($form_elem['userColumn']['name']) && !empty($form_elem['userColumn']['comparator']) && !empty($form_elem['userColumn']['value'])) {
            $query->where("u.{$form_elem['userColumn']['name']}", $form_elem['userColumn']['comparator'], $form_elem['userColumn']['value']);
        }

        //not categorized fields
        if (!empty($form_elem['other']['not-country'])) {
            $query->where('u.country', '<>', $form_elem['other']['not-country']);
        }

        if (!empty($form_elem['other']['newsletter'])) {
            if ($form_elem['other']['newsletter'] == 'yes') {
                $query->whereRaw('u.newsletter = 1');
            } else {
                $query->whereRaw('u.newsletter <> 1');
            }
        }

        $str = '';
        $columns = $this->getUserSearchColumnsList();

        foreach ($columns['select'] as $key => $title) {
            if (in_array($key, $privacy_settings)) {
                unset($columns['select'][$key]);
            }
        }

        foreach ($privacy_settings as $s) {
            if(!$fetch_all && !in_array($s, $visible_columns)) {
                continue;
            }
            $str .= "{$s}.value as {$s}, ";
        }

        foreach ($columns['select'] as $key => $value) {
            if(!$fetch_all && !in_array($key, $visible_columns)) {
                continue;
            }
            $str .= "u.{$key}, ";
        }
        foreach ($columns['especial'] as $key => $value) {
            if(!$fetch_all && !in_array($key, $visible_columns)) {
                continue;
            }
            if ($this->permissions[$key] == true) {
                $str .= "u.{$key}, ";
            } else {
                $str .= "'************' AS {$key}, ";
            }
        }
        if (count($extra_select) > 0) {
            foreach ($extra_select as $key => $value) {
                $str .= "{$value}, ";
            }
        }

        if ($archived) {
            $query->selectRaw("{$str}u.username AS backend, u.id AS playcheck, 'Yes' AS archived");
        } else {
            $query->selectRaw("{$str}u.username AS backend, u.id AS playcheck, '' AS archived");
        }

        return $query;
    }

    public function export(Request $request)
    {
        $users = $this->getUserSearchQuery($request, false, null, true)->orderBy('username', 'asc')->get();
        if ($this->app['vs.config']['archive.db.support'] && $this->not_use_archived == false) {
            $users_archived = $this->getUserSearchQuery($request, true)->get();
            if (count($users_archived) > 0) {
                $data = $users->merge($users_archived);
                $users = $data->sortBy('username', SORT_NATURAL | SORT_FLAG_CASE);
            }
        }

        $records[] = ['User id', 'Username', 'Email', 'Mobile', 'First name', 'Last name', 'Address', 'City', 'Country',
            'Archived', 'Active', 'Cur ip', 'Currency', 'DOB', 'Affiliate id', 'Alias', 'Bonus code', 'Bust threshold',
            'Cash balance', 'Friend', 'Last login', 'Last logout', 'Newsletter', 'Preferred language', 'Reg ip',
            'Register date', 'Sex', 'Verified phone', 'Zip code'];

        foreach ($users as $user) {
            $records[] = [
                $user->id,
                $user->username,
                $user->email,
                $user->mobile,
                $user->firstname,
                $user->lastname,
                $user->address,
                $user->city,
                $user->country,
                $user->archived,
                $user->active,
                $user->cur_ip,
                $user->currency,
                $user->dob,
                $user->affe_id,
                $user->alias,
                $user->bonus_code,
                $user->bust_treshold,
                $user->cash_balance,
                $user->friend,
                $user->logged_in,
                $user->last_logout,
                $user->newsletter,
                $user->preferred_lang,
                $user->reg_ip,
                $user->register_date,
                $user->sex,
                $user->verified_phone,
                $user->zipcode
            ];
        }
        return DownloadHelper::streamAsCsv($this->app, $records, 'users-list');
    }
}
