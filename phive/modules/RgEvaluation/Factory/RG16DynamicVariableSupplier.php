<?php

namespace RgEvaluation\Factory;

class RG16DynamicVariableSupplier extends DynamicVariablesSupplier
{
    public function getSupplier(): TriggerDataSupplier
    {
        return new RG16DataSupplier($this->user);
    }
}
