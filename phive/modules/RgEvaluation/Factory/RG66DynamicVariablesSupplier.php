<?php

namespace RgEvaluation\Factory;

class RG66DynamicVariablesSupplier extends DynamicVariablesSupplier
{
    public function getSupplier(): TriggerDataSupplier
    {
        return new RG66DataSupplier($this->user);
    }
}