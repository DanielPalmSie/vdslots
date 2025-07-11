<?php
/**
 * Created by PhpStorm.
 * User: vadim
 * Date: 2017.01.02.
 */

namespace App\Models;

use App\Extensions\Database\FModel;

class FraudRule extends FModel
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'fraud_rules';

    public $timestamps = false;

    public $fillable = [
        'group_id',
        'country',
        'tbl',
        'field',
        'start_value',
        'end_value',
        'like_value',
        'value_exists',
        'alternative_ids',
        'not_like_value',
        'value_in',
        'value_not_in'
    ];
}
