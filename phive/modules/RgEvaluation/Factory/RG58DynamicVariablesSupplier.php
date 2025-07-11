<?php

namespace RgEvaluation\Factory;

class RG58DynamicVariablesSupplier extends DynamicVariablesSupplier
{
    public function getSupplier(): TriggerDataSupplier
    {
        return new RG58DataSupplier($this->user);
    }
}