<?php

namespace App\Models;

use App\Extensions\Database\FModel;

class PageSetting extends FModel
{
    public $timestamps = false;
    public $table = 'page_settings';
    protected $primaryKey = 'setting_id';

    protected $fillable = ['value'];

}