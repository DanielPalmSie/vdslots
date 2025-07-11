<?php

namespace RgEvaluation\Factory;

class RG77DynamicVariablesSupplier extends DynamicVariablesSupplier
{
    public function getSupplier(): TriggerDataSupplier
    {
        return new RG77DataSupplier($this->user);
    }
}
