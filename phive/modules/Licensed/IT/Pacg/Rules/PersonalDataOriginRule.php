<?php

namespace IT\Pacg\Rules;


use Rakit\Validation\Rule;
use IT\Pacg\Tables\PersonalDataOriginType;

class PersonalDataOriginRule extends Rule
{
    protected $message = ":value is not a valid personal data origin type to be send to the Italian Regulator";

    /**
     * Check the $value is valid
     *
     * @param mixed $value
     * @return bool
     */
    public function check($value): bool
    {
        $allowed = PersonalDataOriginType::getAllowedValues();

        if(in_array($value, $allowed)) {
            return true;
        }

        return false;
    }
}