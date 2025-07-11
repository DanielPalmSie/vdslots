<?php

namespace App\Models;

use App\Extensions\Database\FModel;

class MessagingCampaignUsers extends FModel
{
    protected $table = 'messaging_campaign_users';

    protected $guarded = ['id'];

    public $timestamps = false;



}