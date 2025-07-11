<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 07/02/19
 * Time: 13:19
 */

namespace App\Models;
use App\Extensions\Database\FModel;

class UserChangeStats extends FModel
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users_changes_stats';

    protected $guarded = ['id'];

    public $timestamps = false;

    const COUNTRY = 'country';

    const PROVINCE = 'main_province';

}
