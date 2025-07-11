<?php

namespace RgEvaluation\Factory;


class RG78DynamicVariablesSupplier extends DynamicVariablesSupplier
{
    public function getSupplier(): TriggerDataSupplier
    {
        return new RG78DataSupplier($this->user);
    }
}
