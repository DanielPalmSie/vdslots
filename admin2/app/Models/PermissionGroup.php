<?php

namespace App\Models;

use App\Extensions\Database\FModel;

class PermissionGroup extends FModel
{
    public $timestamps = false;

    public $fillable = [
        'tag',
        'mod_value',
        'permission'
    ];

}
