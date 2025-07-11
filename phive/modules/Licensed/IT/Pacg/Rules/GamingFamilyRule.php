<?php

namespace IT\Pacg\Rules;

use Rakit\Validation\Rule;
use IT\Pacg\Tables\GamingFamily;

class GamingFamilyRule extends Rule
{
    protected $message = ":value is not a valid gaming family to be send to the Italian Regulator";

    /**
     * Check the $value is valid
     *
     * @param mixed $value
     * @return bool
     */
    public function check($value): bool
    {
        $allowed = GamingFamily::getAllowedValues();

        if(in_array($value, $allowed)) {
            return true;
        }

        return false;
    }

}

