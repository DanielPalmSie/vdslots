<?php
/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2016.01.11.
 * Time: 9:42
 */
namespace App\Models;

use App\Extensions\Database\FModel;

class Withdrawal extends FModel
{
    public $timestamps = false;

    protected $primaryKey = 'id';

    protected $table = 'pending_withdrawals';

    public function actor()
    {
        return $this->hasOne('App\Models\User', 'id', 'approved_by');
    }
}