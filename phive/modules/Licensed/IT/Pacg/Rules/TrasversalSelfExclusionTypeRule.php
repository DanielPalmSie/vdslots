<?php

namespace IT\Pacg\Rules;

use IT\Pacg\Tables\TrasversalSelfExclusion;
use IT\Pacg\Tables\TrasversalSelfExclusionManagement;
use Rakit\Validation\Rule;

class TrasversalSelfExclusionTypeRule extends Rule
{
    protected $message = ":value is not a valid self-exclusion type to be send to the Italian Regulator";

    /**
     * Check the $value is valid
     *
     * @param mixed $value
     * @return bool
     */
    public function check($value): bool
    {
        $allowed = TrasversalSelfExclusion::getAllowedValues();

        $exclusion = +$value;
        $management = +$this->getAttribute()->getValue('self_exclusion_management');

        if (
            $management === TrasversalSelfExclusionManagement::$reactivation
            && $exclusion !== TrasversalSelfExclusion::$non_significant
        ) {
            $this->message = $this->getManagementExclusionMismatchMessage();
            return false;
        }

        if (in_array($value, $allowed)) {
            return true;
        }

        return false;
    }

    protected function getManagementExclusionMismatchMessage(): string
    {
        $management = +$this->getAttribute()->getValue('self_exclusion_management');

        return ":attribute = :value can't be used together with self_exclusion_management = $management";
    }

}

