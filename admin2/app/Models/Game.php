<?php
/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2015.11.16.
 * Time: 16:36
 */
namespace App\Models;

use App\Extensions\Database\FModel;
use App\Repositories\GamesRepository;
use App\Repositories\UserRepository;
use Valitron\Validator;
use App\Helpers\DataFormatHelper as Help;

class Game extends FModel
{
    const OP_FEE_DEFAULT=0.15;
    public $timestamps = false;
    protected $table = 'micro_games';
    protected $fillable = [
        'game_name',
        'tag',
        'game_id',
        'languages',
        'ext_game_name',
        'client_id',
        'module_id',
        'width',
        'height',
        'branded',
        'popularity',
        'game_url',
        'meta_descr',
        'bkg_pic',
        'html_title',
        'jackpot_contrib',
        'op_fee',
        'stretch_bkg',
        'played_times',
        'orion_name',
        'device_type',
        'operator',
        'network',
        'active',
        'blocked_countries',
        'retired',
        'device_type_num',
        'payout_percent',
        'min_bet',
        'max_bet',
        'ribbon_pic',
        'enabled',
        'volatility',
        'num_lines',
        'max_win',
        'auto_spin',
        'included_countries',
        'multi_channel',
        'mobile_id',
        'blocked_logged_out',
        'payout_extra_percent',
        'blocked_provinces'
    ];

    public $old_entry = null;
    /**
     * @return array
     */
    public function getTagsList()
    {
        return array_filter(explode(",", $this->tag), function ($el) {
            return !empty($el);
        });
    }

    public function getOpFeeAttribute($val) {
        return empty($val) ? self::OP_FEE_DEFAULT : $val;
    }

    public function validate()
    {
        if (parent::validate() === false) {
            return false;
        }

        $network_countries = [
            'greentube' => 'AL AU AT BY BE BG CA HR CY CZ DK EG EE FR GR HU IE IT LV LT MK ME PL PT RO SK SI ES CH TR US'
        ];

        $rules = [
            'min' => [
                ['payout_percent', 0.001],
                ['mobile_id', 0]
            ],
            'max' => [
                ['payout_percent', 0.999]
            ],
            'unique' => [
                'game_url'
            ],
            'integer' => [
                ['width'],
                ['height'],
                ['min_bet'],
                ['max_bet'],
                ['max_win'],
                ['num_lines']
            ],
            'numeric' => [
                ['op_fee']
            ],
            'required' => [
                ['game_name'],
                ['game_id'],
                ['ext_game_name'],
                ['payout_percent'],
                ['width'],
                ['height']
            ],
            'minMaxBet' => [
                'min_bet'
            ],
            'lengthMin' => [
                ['game_url', 3],
                ['game_name', 3]
            ],
            'mobileIDCheck' => [
                'mobile_id'
            ],
            'countriesCheck' => [
                ['blocked_countries'],
                ['included_countries'],
            ],
            'grefTypenumIdxIndex' => [
                'device_type'
            ],
            'ext_game_name_in_use' => [
                'ext_game_name'
            ],
            'ext_game_name_network' => [
                'ext_game_name'
            ]
        ];

        // return false when error
        $v = new Validator($this->getAttributes());

        $mandatory = $network_countries[$this->network];

        if (!empty($mandatory)) {
            $rules['required'][] = 'blocked_countries';

            // check if there's any country required by network_countries
            // which the user didn't insert in blocked_countries list
            // and trigger a validation error

            Help::getListFromString($mandatory, ' ')
                ->filter(function ($country) {
                    return !str_contains($this->blocked_countries, $country);
                })
                ->tap(function ($list) use ($v, &$rules) {
                    if (count($list) == 0) {
                        return;
                    }
                    $msg_list = $list->implode(' ');
                    $v->addRule('blockedCountries', function () {
                        return false;
                    }, " missing mandatory countries ({$msg_list}).");

                    $rules['blockedCountries'] = ['blocked_countries'];
                });
        }

        $v->addRule('unique', function ($field, $value) {
            if ($value == '') {
                return true;
            }
            return $this
                    ->where($field, '=', $value)
                    ->where('id', '!=', (int)$this->id)
                    ->first() == null;
        }, ' already exists. Please choose another.');

        $v->addRule('countriesCheck', function ($f, $value) {
            return Help::getListFromString($value, ' ')
                    ->filter(function ($item) {
                        return strlen($item) != 2;
                    })->count() == 0;
        }, ' wrong format.');

        $v->addRule('minMaxBet', function ($f, $v, $p, $fields) {

            return $fields['min_bet'] <= $fields['max_bet'];

        }, 'is Greater than Max Bet');

        $v->addRule('mobileIDCheck', function ($f, $value) {

            return empty($value) || preg_match('@^[1-9][0-9]*$@', $value) === 1;

        }, ' is negative or not a valid int.');

        $v->addRule('grefTypenumIdxIndex', function ($f, $value, $p, $fields) {

            return Game::query()
                ->where('device_type_num', $fields['device_type_num'])
                ->where('ext_game_name', $fields['ext_game_name'])
                ->where('id', '!=', $this->id)
                ->count() == 0;

        }, " {$this->device_type} is already set on external game name: {$this->ext_game_name}.");

        $v->addRule('ext_game_name_in_use', function ($f, $value, $p, $fields) {
            // this is create event
            if (empty($this->id) || empty($this->getOriginal('ext_game_name'))) {
                return true;
            }

            $original = self::find($this->id)->getAttribute('ext_game_name');

            // ext_game_name did not change
            if ($original === $fields['ext_game_name']) {
                return true;
            }

            return UserGameSession::query()->where('game_ref', $original)->count() == 0;

        }, " can't change once players started playing this game.");


        $v->addRule('ext_game_name_network', function ($f, $value, $p, $fields) {
            $network = [
                'microgaming' => 'mgs'
            ];

            return starts_with(strtolower($fields['ext_game_name']), strtolower($fields['network']))
                || starts_with(strtolower($fields['ext_game_name']), strtolower($network[$fields['network']]) ?? strtolower($fields['network']));

        }, " must start with network value.");


        $v->rules($rules);

        if (!$v->validate()) {
            $this->overrideErrors($v->errors());
            return false;
        }

        TransLog::query()->create([
            'user_id' => UserRepository::getCurrentId(),
            'tag' => empty($this->id)
                ? 'game-wizard-create'
                : 'game-wizard-edit',
            'dump_txt' => var_export($this->toArray(), true)
        ]);

        return true;
    }

    protected function rules()
    {
        return [
            'default' => [
            ]
        ];
    }

    /**
     * @param $key
     * @return array|string
     */
    public function getOldValue($key) {
        $entry = function($k) {
            if (is_array($this->old_entry)) {
                return $this->old_entry[$k];
            }

            return $this->old_entry->{$k};
        };

        if (is_array($key)) {
            return array_map($entry, $key);
        }

        return $entry($key);
    }

    /**
     * @param array $data
     * @param GamesRepository $games_repo
     * @return $this
     */
    public function customFillAttributes($data, $games_repo) {
        $this->old_entry = $this->getAttributes();
        $this->old_entry->id = $this->old_entry->id ?? 0;

        $this->fill($data);
        $this->max_win = $this->max_win ?? 0;
        $this->client_id = $this->client_id ?? 0;
        $this->module_id = $this->module_id ?? '';
        $this->meta_descr = $this->meta_descr ?? '';
        $this->bkg_pic = $this->bkg_pic ?? '';
        $this->html_title = $this->html_title ?? '';
        $this->played_times = $this->played_times ?? 0;
        $this->orion_name = $this->orion_name ?? '';
        $this->multi_channel = $this->multi_channel ?? 0;
        $this->stretch_bkg = $this->stretch_bkg ?? 0;
        $this->enabled = $this->enabled ?? 0;
        $this->active = (int)$this->active;
        if(p('settings.games.section.payout_extra_percent')) {
            $this->payout_extra_percent = $this->payout_extra_percent ?? 0;
        }
        $this->languages = implode($this->languages, ',');
        $this->tag = $this->tag ?? '';
        $this->blocked_countries = Help::getListFromString($this->blocked_countries, ',')->implode(" ");
        $this->included_countries = Help::getListFromString($this->included_countries, ',')->implode(" ");
        $this->blocked_provinces = Help::getListFromString($this->blocked_provinces, ',')->implode(" ");
        $this->device_type_num = $games_repo->getDevices(true)[$this->device_type];
        $this->op_fee = self::OP_FEE_DEFAULT;

        return $this;
    }


}
