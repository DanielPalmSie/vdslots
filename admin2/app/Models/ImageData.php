<?php

namespace App\Models;

use App\Extensions\Database\FModel;

class ImageData extends FModel
{
    public $timestamps = false;
    public $table = 'image_data';
    protected $primaryKey = 'id';

    public function image_data()
    {
        return $this->hasOne(ImageAlias::class, 'id', 'image_id');
    }

}

