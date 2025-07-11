<?php

namespace RgEvaluation\Factory;

class RG39DynamicVariablesSupplier extends DynamicVariablesSupplier
{
    public function getSupplier(): TriggerDataSupplier
    {
        return new RG39DataSupplier($this->user);
    }
}