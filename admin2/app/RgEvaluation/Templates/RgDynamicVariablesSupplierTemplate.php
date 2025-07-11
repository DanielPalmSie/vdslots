<?= "<?php ";?>

namespace RgEvaluation\Factory;

class <?= $dynamicVariablesSupplierClassName ?> extends DynamicVariablesSupplier
{
    public function getSupplier(): TriggerDataSupplier
    {
        return new <?= $dataSupplierClassName ?>($this->user);
    }
}
