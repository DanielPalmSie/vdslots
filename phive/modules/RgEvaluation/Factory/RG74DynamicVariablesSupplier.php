<?php

namespace RgEvaluation\Factory;

class RG74DynamicVariablesSupplier extends DynamicVariablesSupplier
{
    public function getSupplier(): TriggerDataSupplier
    {
        return new RG74DataSupplier($this->user);
    }
}
