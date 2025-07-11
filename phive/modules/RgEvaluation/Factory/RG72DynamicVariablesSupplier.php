<?php

namespace RgEvaluation\Factory;

class RG72DynamicVariablesSupplier extends DynamicVariablesSupplier
{
    public function getSupplier(): TriggerDataSupplier
    {
        return new RG72DataSupplier($this->user);
    }
}