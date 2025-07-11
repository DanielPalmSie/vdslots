<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 2016.21.16.
 * Time: 10:36
 */

namespace App\Models;

use App\Extensions\Database\FModel;

class UserMonthlyLiability extends FModel
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users_monthly_liability';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'year',
        'month',
        'type',
        'main_cat',
        'sub_cat',
        'transactions',
        'amount',
        'currency',
        'country',
        'source',
        'province'
    ];


    const SOURCE_VIDEOSLOTS = 0;

    const SOURCE_PARTNERROOM = 1;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function currency()
    {
        return $this->hasOne('App\Models\Currency', 'code', 'currency');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function user()
    {
        return $this->hasOne('App\Models\User', 'id', 'user_id');
    }


}
