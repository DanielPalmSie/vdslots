<?php
namespace RgEvaluation\Factory;

class RG68DynamicVariablesSupplier extends DynamicVariablesSupplier
{
    public function getSupplier(): TriggerDataSupplier
    {
        return new RG68DataSupplier($this->user);
    }
}
