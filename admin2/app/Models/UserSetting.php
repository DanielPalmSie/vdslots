<?php
/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2015.11.16.
 * Time: 16:36
 */

namespace App\Models;

use App\Extensions\Database\FModel;

class UserSetting extends FModel
{

    public $timestamps = false;
    public $fillable = [
        'setting',
        'value',
        'created_at'
    ];
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users_settings';
    protected $primaryKey = 'id';

}
