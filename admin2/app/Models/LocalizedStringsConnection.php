<?php

namespace App\Models;

use App\Extensions\Database\FModel;

class LocalizedStringsConnection extends FModel
{
    public $timestamps = false;
    public $table = 'localized_strings_connections';
    protected $primaryKey = 'id';

    protected $fillable = ['target_alias', 'bonus_code'];

}

