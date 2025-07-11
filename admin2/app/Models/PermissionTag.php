<?php

namespace App\Models;

use App\Extensions\Database\FModel;

class PermissionTag extends FModel
{
    public $incrementing = false;

    public $timestamps = false;

    protected $primaryKey = 'tag';

}
