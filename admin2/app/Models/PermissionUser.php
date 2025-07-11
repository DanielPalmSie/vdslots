<?php
/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2015.11.16.
 * Time: 16:36
 */

namespace App\Models;

use App\Extensions\Database\FModel;

class PermissionUser extends FModel
{

    public $timestamps = false;
    public $fillable = [
        'tag',
        'permission'
    ];
    protected $table = 'permission_users';
    protected $primaryKey = 'user_id';

}
