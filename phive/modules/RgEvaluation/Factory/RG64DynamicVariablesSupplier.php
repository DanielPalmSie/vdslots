<?php

namespace RgEvaluation\Factory;

class RG64DynamicVariablesSupplier extends DynamicVariablesSupplier
{
    public function getSupplier(): TriggerDataSupplier
    {
        return new RG64DataSupplier($this->user);
    }
}