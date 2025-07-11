<?php

namespace RgEvaluation\Factory;

class RG25DynamicVariablesSupplier extends DynamicVariablesSupplier
{
    public function getSupplier(): TriggerDataSupplier
    {
        return new RG25DataSupplier($this->user);
    }
}