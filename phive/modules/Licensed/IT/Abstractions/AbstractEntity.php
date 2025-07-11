<?php
namespace IT\Abstractions;

use IT\Traits\ValidationTraitItaly;

require_once __DIR__ . '/../../../../traits/MassAssignmentTrait.php';

/**
 * Class AbstractEntity
 * @package IT\Pacg\Types
 */
abstract class AbstractEntity
{
    use ValidationTraitItaly;

    use \MassAssignmentTrait {
        \MassAssignmentTrait::fill as parentFill;
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return '';
    }

    /**
     * @return array
     */
    public function toValidate(): array
    {
        $values = [];
        foreach($this->fillable as $fillable) {
            $values[$fillable] = $this->$fillable;
        }

        return $values;
    }

    /**
     * @param array $property_values
     * @return AbstractEntity
     * @throws \Rakit\Validation\RuleQuashException
     */
    public function fill(array $property_values): AbstractEntity
    {
        $this->parentFill($property_values);
        $this->validate($this->toValidate());

        return $this;
    }

    /**
     * @param array $array
     * @return array
     */
    abstract public function toArray(array $array = []): array;
}