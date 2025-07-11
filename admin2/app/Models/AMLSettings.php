<?php

namespace App\Models;

use App\Extensions\Database\FModel;

class AMLSettings extends FModel
{
    protected $table = 'risk_profile_rating';

    public $timestamps = false;

    protected $primaryKey = 'name';

    public $incrementing = false;

    public $fillable = [
        'name',
        'title',
        'description',
        'type',
        'score',
        'category',
        'section'
    ];
}