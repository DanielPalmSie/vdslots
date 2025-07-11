<?php

namespace RgEvaluation\Factory;

class RG75DynamicVariablesSupplier extends DynamicVariablesSupplier
{
    public function getSupplier(): TriggerDataSupplier
    {
        return new RG75DataSupplier($this->user);
    }
}
