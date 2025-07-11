<?php

namespace RgEvaluation\Factory;

class RG5DynamicVariableSupplier extends DynamicVariablesSupplier
{
    public function getSupplier(): TriggerDataSupplier
    {
        return new RG5DataSupplier($this->user);
    }
}
