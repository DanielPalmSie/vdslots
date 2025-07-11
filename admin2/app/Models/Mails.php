<?php

namespace App\Models;

use App\Extensions\Database\FModel;

class Mails extends FModel
{
    public $timestamps = false;
    public $table = 'mails';
    protected $primaryKey = 'mail_trigger';

    protected $fillable = ['mail_trigger', 'subject', 'content', 'replacers'];

}

