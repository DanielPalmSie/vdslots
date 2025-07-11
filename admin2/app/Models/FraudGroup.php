<?php
/**
 * Created by PhpStorm.
 * User: vadim
 * Date: 2017.01.02.
 */

namespace App\Models;

use App\Extensions\Database\FModel;

class FraudGroup extends FModel
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'fraud_groups';

    public $timestamps = false;

    public $fillable = [
        'tag',
        'start_date',
        'end_date',
        'description',
        'is_active'
    ];
}
