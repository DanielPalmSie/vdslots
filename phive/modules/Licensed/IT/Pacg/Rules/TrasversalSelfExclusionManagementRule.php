<?php

namespace IT\Pacg\Rules;

use IT\Pacg\Tables\TrasversalSelfExclusionManagement;
use Rakit\Validation\Rule;

class TrasversalSelfExclusionManagementRule extends Rule
{
    protected $message = ":value is not a valid self-exclusion management to be send to the Italian Regulator";

    /**
     * Check the $value is valid
     *
     * @param mixed $value
     * @return bool
     */
    public function check($value): bool
    {
        $allowed = TrasversalSelfExclusionManagement::getAllowedValues();

        if(in_array($value, $allowed)) {
            return true;
        }

        return false;
    }

}

