<?php

namespace RgEvaluation\Factory;

class RG24DynamicVariablesSupplier extends DynamicVariablesSupplier
{
    public function getSupplier(): TriggerDataSupplier
    {
        return new RG24DataSupplier($this->user);
    }
}