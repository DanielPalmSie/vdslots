<?php
/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2016.02.03.
 * Time: 13:55
 */

namespace App\Models;

use App\Extensions\Database\FModel;

class BonusEntry extends FModel
{
    public $timestamps = false;
    protected $primaryKey = 'id';

    public function bonus_type()
    {
        return $this->hasOne(BonusType::class, 'id', 'bonus_id');
    }

    public function cash_transactions()
    {
        return $this->hasMany(CashTransaction::class, 'entry_id', 'id');
    }
}
