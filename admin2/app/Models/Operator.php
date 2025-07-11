<?php
/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2015.11.16.
 * Time: 16:36
 */

namespace App\Models;

use App\Extensions\Database\FModel;

class Operator extends FModel
{
    const OP_FEE_DEFAULT=0.15;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'network',
        'branded_op_fee',
        'non_branded_op_fee',
        'blocked_countries',
        'blocked_countries_non_branded',
        'blocked_countries_jackpot'
    ];

    public function getBrandedOpFeeAttribute($val) {
        return empty($val) ? self::OP_FEE_DEFAULT : $val;
    }
    public function getNonBrandedOpFeeAttribute($val) {
        return empty($val) ? self::OP_FEE_DEFAULT : $val;
    }

}
