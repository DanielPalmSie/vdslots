<?php

namespace App\Models;

use App\Extensions\Database\FModel;

class GroupMember extends FModel
{
    public $timestamps = false;

    protected $table = 'groups_members';

}