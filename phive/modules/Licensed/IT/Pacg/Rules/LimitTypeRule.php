<?php

namespace IT\Pacg\Rules;

use Rakit\Validation\Rule;
use IT\Pacg\Tables\GamblingAccountLimitType;

class LimitTypeRule extends Rule
{
    protected $message = ":value is not a valid limit type to be send to the Italian Regulator";

    /**
     * Check the $value is valid
     *
     * @param mixed $value
     * @return bool
     */
    public function check($value): bool
    {
        $allowed = GamblingAccountLimitType::getAllowedValues();

        if(in_array($value, $allowed)) {
            return true;
        }

        return false;
    }

}

