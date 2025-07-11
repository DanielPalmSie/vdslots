<?php

namespace RgEvaluation\Factory;


class RG81DynamicVariablesSupplier extends DynamicVariablesSupplier
{
    public function getSupplier(): TriggerDataSupplier
    {
        return new RG81DataSupplier($this->user);
    }
}
