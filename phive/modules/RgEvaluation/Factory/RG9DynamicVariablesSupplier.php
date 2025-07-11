<?php

namespace RgEvaluation\Factory;

class RG9DynamicVariablesSupplier extends DynamicVariablesSupplier
{
    public function getSupplier(): TriggerDataSupplier
    {
        return new RG9DataSupplier($this->user);
    }
}