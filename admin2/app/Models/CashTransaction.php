<?php
/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2016.01.11.
 * Time: 14:52
 */
namespace App\Models;

use App\Extensions\Database\FModel;

class CashTransaction extends FModel
{
    public $timestamps = false;
    protected $table = 'cash_transactions';
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    public function ip_log()
    {
        return $this->hasOne('App\Models\IpLog', 'tr_id', 'id')->where('tag', 'cash_transactions');
    }

}