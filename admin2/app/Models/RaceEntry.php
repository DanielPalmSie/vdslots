<?php
/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2015.11.16.
 * Time: 16:36
 */

namespace App\Models;

use App\Extensions\Database\FModel;

class RaceEntry extends FModel
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'race_entries';

    public function race()
    {
        return $this->hasOne('App\Models\Race', 'id', 'r_id');
    }

    public function cashTransactions()
    {
        return $this->hasMany('App\Models\CashTransaction');
    }

}
