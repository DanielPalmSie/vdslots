<?php

namespace App\Models;

use App\Extensions\Database\FModel;

class ImageAliasConnection extends FModel
{
    public $timestamps = false;
    public $table = 'image_aliases_connections';
    protected $primaryKey = 'id';

    protected $fillable = ['image_alias', 'bonus_code'];

    public function image_alias()
    {
        return $this->belongsTo(ImageAlias::class, 'image_alias', 'image_alias');
    }

}

