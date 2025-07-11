<?php

namespace RgEvaluation\Factory;

class NullObjectDynamicVariablesSupplier extends DynamicVariablesSupplier
{
    public function getSupplier(): TriggerDataSupplier
    {
        return new NullObjectDataSupplier($this->user);
    }
}