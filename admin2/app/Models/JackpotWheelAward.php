<?php

namespace App\Models;

use App\Extensions\Database\FModel;
use Valitron\Validator;

class JackpotWheelAward extends FModel
{
    public function slices()
    {
        return $this->belongsToMany('App\Models\JackpotWheelSlice');
    }

    protected function rules()
    {
        return [
            'default' => [
                'required' => [['name'], ['award_type']],
            ]
        ];
    }

    public function validate()
    {
        if (parent::validate() === false) {
            return false;
        }

        $validator = new Validator($this->getAttributes());

        if($this->award_type == 'jackpot') {
            $validator->rule('required', 'jackpot_id');
            $validator->rule('integer', 'jackpot_id');
        } else {
            $validator->rule('required', 'prize');
            $validator->rule('integer', 'prize');
        }

        if (!$validator->validate()) {
            $this->overrideErrors($validator->errors());
            return false;
        } else {
            return true;
        }

        return true;
    }
}

