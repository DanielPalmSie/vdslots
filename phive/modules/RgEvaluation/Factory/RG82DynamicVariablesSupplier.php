<?php

namespace RgEvaluation\Factory;

class RG82DynamicVariablesSupplier extends DynamicVariablesSupplier
{
    public function getSupplier(): TriggerDataSupplier
    {
        return new RG82DataSupplier($this->user);
    }
}
