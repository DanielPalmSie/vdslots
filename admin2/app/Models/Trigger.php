<?php

namespace App\Models;

use App\Extensions\Database\FModel;

class Trigger extends FModel
{
    protected $table = 'triggers';

    public $timestamps = false;
    
    protected $primaryKey = 'name';
    
    public $incrementing = false;
    
    public $fillable = [
        'name',
        'indicator_name',
        'description',
        'color',
        'score'
    ];
}
