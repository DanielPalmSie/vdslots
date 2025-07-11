<?php

namespace App\Models;

use App\Extensions\Database\FModel;

class Jackpot extends FModel
{

    public function validate()
    {
        if (parent::validate() === false) {
            return false;
        }

        return true;
    }

}


