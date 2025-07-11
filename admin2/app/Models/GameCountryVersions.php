<?php

namespace App\Models;

use App\Extensions\Database\FModel;

class GameCountryVersions extends FModel
{
    public $timestamps = false;

    protected $table = 'game_country_versions';

    protected $primaryKey = 'id';

    protected $fillable = [
        'game_id',
        'country',
        'game_version',
        'game_certificate_ref'
    ];

}
