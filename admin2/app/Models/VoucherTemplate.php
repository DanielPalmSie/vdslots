<?php
/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2016.10.24.
 * Time: 11:42
 */

namespace App\Models;

use App\Extensions\Database\FModel;
use App\Helpers\DateHelper;
use App\Repositories\ReplacerRepository;
use Valitron\Validator;

class VoucherTemplate extends FModel
{
    public $guarded = ['id'];

    protected function rules()
    {
        return [
            'default' => [
                'required' => [['voucher_name'],['count'], ['exclusive']],
                'integer' => 'count',
                'min' => [['count', 1]],
            ]
        ];
    }

    public function validate()
    {
        if (parent::validate() === false) {
            return false;
        }

        $validator = new Validator($this->getAttributes());

        if (!empty($this->bonus_type_template_id) && empty($this->trophy_award_id)) {
            if (!BonusTypeTemplate::find($this->bonus_type_template_id)) {
                $validator->error('bonus_template_missing', "Bonus template does not exist.");
            }
        } elseif (empty($this->bonus_type_template_id) && !empty($this->trophy_award_id)) {
            if (!TrophyAwards::find($this->trophy_award_id)) {
                $validator->error('reward_missing', "Reward does not exist.");
            }
        } elseif (empty($this->bonus_type_template_id) && empty($this->trophy_award_id)) {
            $validator->error('bonus_template_missing', "Bonus or reward needed.");
        }

        $voucherTemplates = VoucherTemplate::where('voucher_code', $this->voucher_code)->get();
        $voucherTemplateCount = $voucherTemplates->count();

        $res = true;

        if ($voucherTemplateCount > 1 ||
            $voucherTemplateCount == 1 && $voucherTemplates->first()->id != $this->id)
        {
            if (ReplacerRepository::replaceDate($this->voucher_code) == $this->voucher_code)
                $res = false;

            $validator->error('dublicate_voucher_code', "The Voucher code has already been used");
        }

        $dates = [
            [$this->expire_time, 'invalid_expire_time', 'The expire time is invalid'],
            [$this->deposit_start, 'invalid_deposit_start', 'The deposit period is invalid'],
            [$this->deposit_end, 'invalid_deposit_end', 'The deposit period is invalid'],
            [$this->wager_start, 'invalid_wager_start', 'The wager period is invalid'],
            [$this->wager_end, 'invalid_wager_end', 'The wager period is invalid'],
        ];

        foreach ($dates as $date) {
            if (!empty($date[0]) && !DateHelper::validateCarbonString($date[0])) {
                $res = false;
                $validator->error($date[1], $date[2]);
            }
        }

        if (!$validator->validate()) {
            $this->overrideErrors($validator->errors());
        }

        return $res;
    }

    public function doReplacers()
    {
        $this->voucher_name = ReplacerRepository::replaceDate($this->voucher_name);
        $this->voucher_code = ReplacerRepository::replaceDate($this->voucher_code);
        $this->expire_time = ReplacerRepository::replaceExpireTime($this->expire_time);
    }

    public function bonusTypeTemplate()
    {
        return $this->hasOne(BonusTypeTemplate::class, 'id', 'bonus_type_template_id');
    }

    public function trophyAward()
    {
        return $this->hasOne(TrophyAwards::class, 'id', 'trophy_award_id');
    }
}
