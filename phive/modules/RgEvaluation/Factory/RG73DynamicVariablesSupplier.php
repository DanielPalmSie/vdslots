<?php

namespace RgEvaluation\Factory;

class RG73DynamicVariablesSupplier extends DynamicVariablesSupplier
{
    public function getSupplier(): TriggerDataSupplier
    {
        return new RG73DataSupplier($this->user);
    }
}