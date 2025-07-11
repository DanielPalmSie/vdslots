<?php

namespace IT\Pacg\Rules;


use IT\Pacg\Tables\BonusOperationReasonCode;
use Rakit\Validation\Rule;

class BonusOperationReasonTypeRule extends Rule
{
    protected $message = ":value is not a valid bonus cancelation type to be send to the Italian Regulator";

    /**
     * Check the $value is valid
     *
     * @param mixed $value
     * @return bool
     */
    public function check($value): bool
    {
        $allowed = BonusOperationReasonCode::getAllowedValues();

        if(in_array($value, $allowed)) {
            return true;
        }

        return false;
    }
}