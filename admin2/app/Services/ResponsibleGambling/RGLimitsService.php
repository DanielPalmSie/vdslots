<?php

namespace App\Services\ResponsibleGambling;

use BadMethodCallException;

/**
 * @method void refreshCustomerNetDepositLimit(string $action) Forwarded to phive('RgLimits') module
 */
class RGLimitsService
{
    private $rgLimits;

    public function __construct()
    {
        $this->rgLimits = rgLimits();
    }

    public function __call($name, $arguments) {
        if (method_exists($this->rgLimits, $name)) {
            return call_user_func_array([$this->rgLimits, $name], $arguments);
        }

        throw new BadMethodCallException("Method {$name} does not exist in class " . __CLASS__);
    }
}