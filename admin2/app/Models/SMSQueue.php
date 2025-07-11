<?php
/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2016.10.07.
 * Time: 17:22
 */

namespace App\Models;

use App\Extensions\Database\FModel;

class SMSQueue extends FModel
{
    protected $table = 'sms_queue';

    protected $guarded = ['id'];

    public $timestamps = false;

}