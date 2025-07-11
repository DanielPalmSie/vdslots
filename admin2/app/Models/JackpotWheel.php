<?php

namespace App\Models;

use App\Extensions\Database\FModel;

class JackpotWheel extends FModel
{
    public $fillable = [
        "name",
        "number_of_slices",
        "cost_per_spin",
        "active",
        "deleted",
        "created_at",
        "updated_at",
        "country",
        "style",
        "excluded_countries"
    ];

    public function slices()
    {
        return $this->hasMany('App\Models\JackpotWheelSlice', 'wheel_id', 'id');
    }

    public function wheelLogs()
    {
        return $this->hasMany('App\Models\JackpotWheelLog', 'wheel_id', 'id');
    }

    protected function rules()
    {
        return [
            'default' => [
                'required' => [['name'], ['number_of_slices'], ['cost_per_spin']]
            ]
        ];
    }

    public function validate()
    {
        if (parent::validate() === false) {
            return false;
        }

        return true;
    }

    public function getExcludedCountriesAttribute($value)
    {
        return explode(" ", $value);
    }

}

