<?php

namespace App\Models;

use App\Extensions\Database\FModel;

class MailsConnections extends FModel
{
    public $timestamps = false;
    public $table = 'mails_connections';
    protected $primaryKey = 'id';

    protected $fillable = ['mail_trigger_target', 'bonus_code'];

}

