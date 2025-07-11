<?php
/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2016.10.18.
 * Time: 13:59
 */

namespace App\Models;

use App\Extensions\Database\FModel;
use App\Repositories\ReplacerRepository;
use Valitron\Validator;

class BonusTypeTemplate extends FModel
{
    public $guarded = ['id'];

    public function doReplacers()
    {
        $this->expire_time = ReplacerRepository::replaceExpireTime($this->expire_time);
        $this->bonus_code = ReplacerRepository::replaceDate($this->bonus_code);
        $this->reload_code = ReplacerRepository::replaceDate($this->reload_code);
        $this->bonus_tag = !empty($this->bonus_tag) ? $this->bonus_tag : '';
    }

    public function validate()
    {
        if (parent::validate() === false) {
            return false;
        }

        $validator = new Validator($this->getAttributes());
        $validator->labels($this->labels());

        $res = true;

        foreach (['bonus_code', 'reload_code'] as $key)
        {
            $code = $this->getAttribute($key);
            if (!empty($code)) {
                $sameCodeTemplates = BonusTypeTemplate::where($key, '=', $code)->get();

                if ($sameCodeTemplates->count() > 1 ||
                    $sameCodeTemplates->count() == 1 && $sameCodeTemplates->first()->id != $this->id)
                {

                    if (ReplacerRepository::replaceDate($code) == $code)
                        $res = false;
                    $validator->error('dublicate_'.$key, $key === 'bonus_code' ? "The Bonus code has already been used" : "The Reload code has already been used" );
                }
            }
        }

        if (!$validator->validate()) {
            $this->overrideErrors($validator->errors());
        }

        return $res;
    }

    protected function labels()
    {
        return [
            'named_search_id' => 'Contacts filter list',
            'scheduled_time' => 'Scheduled time',
            'recurring_type' => 'Recurring type',
            'start_time' => 'Recurring start time',
            'duplicate_bonus_code' => 'Duplicate Bonus code',
            'duplicate_reload_code' => 'Duplicate Reload code'
        ];
    }
}
