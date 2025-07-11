<?php

namespace IT\Pacg\Rules;


use IT\Pacg\Tables\LegalEntityAccountType;
use Rakit\Validation\Rule;

class LegalEntityAccountTypeRule extends Rule
{
    protected $message = ":value is not a valid legal entity account type to be send to the Italian Regulator";

    /**
     * Check the $value is valid
     *
     * @param mixed $value
     * @return bool
     */
    public function check($value): bool
    {
        $allowed = LegalEntityAccountType::getAllowedValues();

        if(in_array($value, $allowed)) {
            return true;
        }

        return false;
    }
}
