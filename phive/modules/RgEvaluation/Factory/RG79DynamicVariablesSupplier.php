<?php

namespace RgEvaluation\Factory;

class RG79DynamicVariablesSupplier extends DynamicVariablesSupplier
{
    public function getSupplier(): TriggerDataSupplier
    {
        return new RG79DataSupplier($this->user);
    }
}
