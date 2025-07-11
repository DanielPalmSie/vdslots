<?php

namespace RgEvaluation\Factory;


class RG76DynamicVariablesSupplier extends DynamicVariablesSupplier
{
    public function getSupplier(): TriggerDataSupplier
    {
        return new RG76DataSupplier($this->user);
    }
}
