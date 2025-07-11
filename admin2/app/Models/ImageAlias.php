<?php

namespace App\Models;

use App\Extensions\Database\FModel;

class ImageAlias extends FModel
{
    public $timestamps = false;
    public $table = 'image_aliases';
    protected $primaryKey = 'alias';

    protected $fillable = ['alias', 'image_id'];

    public function image_data()
    {
        return $this->hasMany(ImageData::class, 'image_id', 'id');
    }

}

