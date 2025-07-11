<?php


namespace App\Models;

use App\Extensions\Database\FModel;

class TriggersLog extends FModel
{
    public $timestamps = false;
    protected $table = 'triggers_log';
    protected $guarded = ['id'];
    protected $primaryKey = 'id';
    protected $fillable = [
        'user_id',
        'trigger_name',
        'created_at',
        'descr',
        'data',
        'cnt',
        'txt'
    ];
}