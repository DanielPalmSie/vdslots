<?php
/**
 * Created by PhpStorm.
 * User: iondum
 * Date: 04/01/18
 * Time: 13:56
 */

namespace App\Models;

use App\Extensions\Database\FModel;


class CrmSentMailsEvents extends FModel
{
    protected $table = 'crm_sent_mails_events';

    public $timestamps = false;

    protected $guarded = ['id'];

    public function actorUser()
    {
        return $this->belongsTo('App\Models\User', 'actor', 'id');
    }

    public function targetUser()
    {
        return $this->belongsTo('App\Models\User', 'actor', 'id');
    }
}
