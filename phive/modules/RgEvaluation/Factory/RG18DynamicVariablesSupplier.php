<?php

namespace RgEvaluation\Factory;

class RG18DynamicVariablesSupplier extends DynamicVariablesSupplier
{
    public function getSupplier(): TriggerDataSupplier
    {
        return new RG18DataSupplier($this->user);
    }
}