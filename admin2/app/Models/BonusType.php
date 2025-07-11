<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 23/02/16
 * Time: 15:07
 */
namespace App\Models;

use App\Extensions\Database\FModel;
use Valitron\Validator;
use App\Extensions\Database\FManager as DB;

class BonusType extends FModel
{
    public $timestamps = false;
    public $guarded = ['id'];
    protected $primaryKey = 'id';
    protected $fillable = [
        'expire_time',
        'num_days',
        'cost',
        'reward',
        'bonus_name',
        'deposit_limit',
        'rake_percent',
        'bonus_code',
        'deposit_multiplier',
        'bonus_type',
        'exclusive',
        'bonus_tag',
        'type',
        'game_tags',
        'cash_percentage',
        'max_payout',
        'reload_code',
        'excluded_countries',
        'deposit_amount',
        'deposit_max_bet_percent',
        'bonus_max_bet_percent',
        'max_bet_amount',
        'included_countries',
        'fail_limit',
        'game_percents',
        'loyalty_percent',
        'top_up',
        'stagger_percent',
        'ext_ids',
        'progress_type',
        'deposit_threshold',
        'game_id',
        'allow_race',
        'forfeit_bonus',
        'deposit_active_bonus',
        'frb_coins',
        'frb_denomination',
        'frb_lines',
        'frb_cost',
        'award_id',
        'keep_winnings',
		'auto_activate_bonus_id',
		'auto_activate_bonus_day',
		'auto_activate_bonus_period',
		'auto_activate_bonus_send_out_time',
        'allow_xp_calc',
    ];

    protected function rules()
    {
        return [
            'default' => [
                'required' => [['bonus_name']],
                'lengthMin' => [['bonus_name', 3]],
            ]
        ];
    }

    public function bonusEntries()
    {
        return $this->hasMany(BonusEntry::class, 'bonus_id', 'id');
    }

    public function reward()
    {
        return $this->belongsTo(TrophyAwards::class, 'award_id');
    }

    /**
     * By default, if included_countries is empty string, consider all countries included.
     * @param $country
     * @return bool
     */
    public function isCountryIncluded($country)
    {
        return !empty($this->included_countries)
            ?   strpos(strtolower($this->included_countries), strtolower($country)) !== false
            :   true;
    }

    /**
     * By default, if excluded_countries is empty string, consider no country excluded.
     * @param $country
     * @return bool
     */
    public function isCountryExcluded($country)
    {
        return !empty($this->excluded_countries)
            ?   strpos(strtolower($this->excluded_countries), strtolower($country)) !== false
            :   false;
    }

    public function validate()
    {
        if (parent::validate() === false) {
            return false;
        }

        $validator = new Validator($this->getAttributes());
        $validator->labels($this->labels());

        $validBonusTypes = $this->getDistinct('bonus_type');
        $validProgressTypes = $this->getDistinct('progress_type');

        $validator->rule('in', 'bonus_type', $validBonusTypes)->message("Invalid bonus type");
        $validator->rule('in', 'progress_type', $validProgressTypes)->message("Invalid progress type");

        foreach (['bonus_code', 'reload_code'] as $key)
        {
            $code = $this->getAttribute($key);
            if (!empty($code))
            {
                $sameCodeTypes = BonusType::where($key, '=', $code)->get();
                if ($sameCodeTypes->count() > 1 ||
                    $sameCodeTypes->count() == 1 && $sameCodeTypes->first()->id != $this->id)
                {
                    $validator->error('dublicate_'.$key, $key === 'bonus_code' ? "The Bonus code has already been used" : "The Reload code has already been used" );
                }
            }
        }

        if (!$validator->validate()) {
            $this->overrideErrors($validator->errors());
            return false;
        }

        return true;
    }

    public static function getColumnsData()
    {
        $instance = new static;
        $column_data = DB::select('SHOW COLUMNS FROM ' . $instance->getTable());
        $adapted_column_data = [];

        foreach ($column_data as $value) {
            $type_simplified = "text";

            $pos_int = stripos($value->Type, 'int');
            if ($pos_int !== false) {
                $type_simplified = 'number';
            }

            $pos_date = stripos($value->Type, 'date');
            if ($pos_date !== false) {
                $type_simplified = 'date';
            }

            $adapted_column_data[$value->Field] = ['type' => $value->Type, 'type_simple' => $type_simplified, 'NULL' => $value->Null == "NO", 'default' => $value->Default];
        }

        return $adapted_column_data;
    }

    public function storeChanges(string $method, BonusType $newValues, ?BonusType $oldValues = null): void
    {
        $this->logChanges($method, $newValues, $oldValues);
        $this->sendChangesMail($method, $newValues, $oldValues);
    }

    private function logChanges(string $method, BonusType $newValues, ?BonusType $oldValues)
    {
        if ($method === 'add') {
            BoAuditLog::instance()
                ->setTarget('bonus_types', $newValues->id)
                ->setContext('bonus_types', $newValues->id)
                ->registerCreate($newValues->getAttributes());

            return;
        }

        BoAuditLog::instance()
            ->setTarget('bonus_types', $newValues->id)
            ->setContext('bonus_types', $newValues->id)
            ->registerUpdate($oldValues->getAttributes(), $newValues->getAttributes());
    }

    private function sendChangesMail(string $method, BonusType $newValues, ?BonusType $oldValues): void
    {
        $email = phive('MailHandler2')->getSetting('CONFIG_MAIL');
        $replacers = $this->getMailReplacers(
            $method,
            $newValues->getAttributes(),
            $oldValues ? $oldValues->getAttributes() : []
        );

        $mailTrigger = $this->getMailTrigger($method);

        phive('MailHandler2')->sendMailToEmail($mailTrigger, $email, $replacers);
    }

    private function getMailReplacers(string $method, array $newValues, array $oldValues): array
    {
        $replacers = [
            '_TIMESTAMP_' => phive()->hisNow(),
            '__MADE-BY__' => cu()->getUsername()
        ];

        if ($method === 'add') {
            foreach ($newValues as $key => $newValue) {
                $replacers["__NEW-BONUS-TYPE-{$key}__"] = $newValue;
            }

            return $replacers;
        }

        foreach ($newValues as $key => $newValue) {
            $oldValue = $oldValues[$key];

            if ($this->shouldHideFieldInMail($key, $newValue, $oldValue)) {
                $oldValue = $newValue = '-';
            }

            $replacers["__OLD-BONUS-TYPE-{$key}__"] = $oldValue;
            $replacers["__NEW-BONUS-TYPE-{$key}__"] = $newValue;
        }

        return $replacers;
    }

    private function shouldHideFieldInMail(string $key, $newValue, $oldValue): bool
    {
        $identificationFields = ['id', 'bonus_name'];
        return $oldValue == $newValue && !in_array($key, $identificationFields);
    }

    private function getMailTrigger(string $method): string
    {
        return $method === 'edit' ? 'bonus-type.change' : 'bonus-type.add';
    }

    public function getDistinct($column)
    {
        if ($column === 'bonus_type') {

            $config = include __DIR__ . '/../../config/bonustypes.php';

            return $config['types'];

        } elseif ($column === 'progress_type') {

            $config = include __DIR__ . '/../../config/bonustypes.php';

            return $config['progress_types'];
        }

        return parent::getDistinct($column);
    }
}
