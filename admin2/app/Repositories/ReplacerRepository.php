<?php

namespace App\Repositories;

use App\Helpers\DataFormatHelper;
use App\Models\BonusType;
use App\Models\Currency;
use App\Models\Game;
use App\Models\TrophyAwards;
use App\Models\User;
use Carbon\Carbon;

class ReplacerRepository
{
    private $currency_modifiers = [];

    private $data = [];

    public function __construct()
    {
        foreach (Currency::all() as $cm) {
            $this->currency_modifiers[$cm->code] = ['mod' => $cm->mod, 'symbol' => $cm->symbol];
        }
    }

    /**
     *
     *
     * @return array
     */
    public function getAllowedReplacers()
    {
        return array_merge(array_keys($this->getDefaultReplacers()), array_keys($this->getBonusReplacers()), array_keys($this->getVoucherReplacers()));
    }

    public function getDefaultReplacers(User $user = null)
    {
        return [
            '__USERNAME__' => $user->username,
            '__FULLNAME__' => !empty($user) ? $user->getFullName() : null,
            '__USERID__' => $user->id,
            '__CURRENCY__' => $user->currency,
            '__EMAIL__' => $user->email,
            '__COUNTRY__' => $user->country,
            '__FIRSTNAME__' => $user->firstname,
            '__LASTNAME__' => $user->lastname,
            '__MOBILE__' => $user->mobile,
            '__ADDRESS__' => $user->address,
            '__CITY__' => $user->city,
            '__ALIAS__' => $user->alias,
            '__CURSYM__' => $this->currency_modifiers[$user->currency]['symbol'],
            '__FREESPINS[]__' =>  $this->replacerFromBonusTypes('freespins', $user->bonus_code),
            '__WELCOMEBONUS[]__' =>  $this->replacerFromBonusTypes('welcomebonus', $user->bonus_code),

        ];
    }

    public function getBonusReplacers(BonusType $bonus = null, TrophyAwards $award = null, User $user = null)
    {
        $content_alias = $award['type'] == 'freespin-bonus' ? 'free.spin' : 'free.cash';
        if (!empty($user) && empty($this->data['bonus_game_name'])) {
            $game_id = empty($award) ? $bonus->game_id : $award->bonus()->first()->game_id;
            $this->data['bonus_game_name'] = !empty($game_id) ? Game::where('game_id', $game_id)->first()->game_name : null;
        }

        return [
            '__B_BONUSCODE__' => $bonus['bonus_code'],
            '__B_RELOADCODE__' => $bonus['reload_code'],
            '__B_BONUSNAME__' => DataFormatHelper::getBonusName($bonus['bonus_name'], $this->currency_modifiers[$user->currency]),
            '__B_REWARD__' => number_format($bonus['reward'] / 100, 2),
            '__B_EXPIRETIME__' => $bonus['expire_time'],
            '__B_NUMDAYS__' => $bonus['num_days'],
            '__B_GAME__' => $this->data['bonus_game_name'],
            '__B_WAGERREQ__' => $bonus['rake_percent'] / 100,
            '__B_AMOUNT__' => $this->mc($bonus['deposit_limit'] / 100, $user->currency),
            '__B_EXTRAAMOUNT__' => !empty($award) ? $award['amount'] : null,
            '__B_EXTRA__' => !empty($award) ? t($content_alias, $user->getLang()) : null,
        ];
    }

    public function getVoucherReplacers($voucher = null, TrophyAwards $award = null, BonusType $bonus = null, User $user = null)
    {
        if (!empty($user) && empty($this->data['voucher_game_name'])) {
            $game_id = empty($award) ? $bonus->game_id : $award->bonus()->first()->game_id;
            $this->data['voucher_game_name'] = !empty($game_id) ? Game::where('game_id', $game_id)->first()->game_name : null;
        }

        return [
            '__V_VOUCHERNAME__' => $voucher['voucher_name'],
            '__V_VOUCHERCODE__' => $voucher['voucher_code'],
            '__V_AMOUNT__' => !empty($bonus) ? round($this->mc($bonus['reward'], $user->currency) / 100) : $award['valid_days'],
            '__V_DAYS__' => $award['valid_days'],
            '__V_COUNT__' => $voucher['count'],
            '__V_GAME__' => $this->data['voucher_game_name'],
            '__V_SPINS__' =>  !empty($bonus) ? $bonus['reward'] : $award['amount']
        ];
    }

    public function mc($amount, $currency, $op = 'multi', $round = true)
    {
        $mod = empty($this->currency_modifiers[$currency]['mod']) ? 1 : $this->currency_modifiers[$currency]['mod'];
        if ($op == 'multi') {
            return $round ? round($amount * $mod) : $amount * $mod;
        } else {
            return $round ? round($amount / $mod) : $amount / $mod;
        }
    }

    public function replaceKeywords($content, $replacers)
    {
        if ($replacers !== null) {
            $content = strtr($content, $replacers);
        }

        if (preg_match_all('/(__\w+?\[)\d+(\]?__)/', $content, $matches)) {
            $new_replacers = [];

            $matches_rotated = $matches;
            array_unshift($matches_rotated, null);
            $matches_rotated = call_user_func_array('array_map', $matches_rotated);

            foreach ($matches_rotated as $m) {

                // replacer with default values ei. __B_WELCOMEBONUS[300]__
                if (preg_match('/\[(\d+)\]/', $m[0], $matches)) {
                    $new_replacers[$m[0]] = $replacers[$m[1].$m[2]];
                } else {
                    throw new \Exception("Missing replacer {$m[0]}");
                }
            }
            $content = strtr($content, $new_replacers);
        }

        return $content;
    }

    public function getRequiredReplacers($content, $replacers)
    {
        if (empty($content) || empty($replacers)) {
            return [];
        }
        $required_replacers = [];
        
        foreach ($replacers as $key => $value) {
            if (str_contains($content, $key)) {
                $required_replacers[$key] = $value;
            }
        }
        
        if (preg_match_all('/(__\w+?\[)\d+(\]?__)/', $content, $matches)) {
            $new_replacers = [];

            $matches_rotated = $matches;
            array_unshift($matches_rotated, null);
            $matches_rotated = call_user_func_array('array_map', $matches_rotated);

            foreach ($matches_rotated as $m) {
                // replacer with default values ei. __B_WELCOMEBONUS[300]__
                if (preg_match('/\[(\d+)\]/', $m[0], $matches)) {
                    $new_replacers[$m[0]] = $replacers[$m[1].$m[2]];
                } else {
                    throw new \Exception("Missing replacer {$m[0]}");
                }
            }
            $required_replacers = array_merge($required_replacers, $new_replacers);
        }

        return $required_replacers;
    }
    
    /**
     * Deals with time string on expire time field
     *
     * @param $string
     * @return mixed
     */
    public static function replaceExpireTime($string)
    {
        $res = new Carbon($string);
        return $res->toDateString();
    }

    /**
     * Deals with VS200{{date|W}}
     *
     * @param $string
     * @return mixed
     */
    public static function replaceDate($string)
    {
        $string = str_replace('{{date}}', '{{date|dmy}}', $string);
        return preg_replace_callback('/\{\{date\|(.*)\}\}/', function ($matches) {
            return date($matches[1]);
        }, $string);
    }

    private function replacerFromBonusTypes($type, $bonus_code)
    {
        if(!empty($bonus_code)) {
            $result = BonusType::where('bonus_code', $bonus_code)->where('bonus_name', 'like', '%' . $type . '%')->pluck('bonus_name')->first();
            if(!empty($result)) {

                // get the modm value from the result, and use that as $arr[1]
                $start_position = strpos($result, "{$type}:") + strlen($type) + 1;
                $end_position = strpos($result, '}}', $start_position);
                $length = $end_position - $start_position;
                $string = substr($result, $start_position, $length);
                return (int) $string;
            } else {

                // return default value, for freespins its 11, for welcome bonus its 200
                return $type == 'freespins' ? 11 : 200;
            }
        } else {

            // return default value
            return $type == 'freespins' ? 11 : 200;
        }
    }

}