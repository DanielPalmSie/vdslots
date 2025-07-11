<?php

namespace RgEvaluation\Factory;


class RG80DynamicVariablesSupplier extends DynamicVariablesSupplier
{
    public function getSupplier(): TriggerDataSupplier
    {
        return new RG80DataSupplier($this->user);
    }
}
