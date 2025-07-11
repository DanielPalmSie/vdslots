<?php
namespace IT\Pacg\Services;

use IT\Abstractions\AbstractEntity;
use IT\Pacg\Types\DateType;

/**
 * Class SummaryOfServiceOperationsEntity
 * @package IT\Pacg\Services
 */
class SummaryOfServiceOperationsEntity extends PacgService
{
    public $date;

    protected $fillable = [
        'date',
    ];

    protected $rules = [
        'date' => 'required|array',
    ];

    /**
     * @param array $property_values
     * @return AbstractEntity
     */
    public function fill(array $property_values): AbstractEntity
    {
        parent::fill($property_values);

        if (is_array($this->date)) {
            $this->date = (new DateType())->fill($this->date);
        }

        return $this;
    }

    /**
     * @param array $array
     * @return array
     */
    public function toArray(array $array = []): array
    {
        $values = [
            "data" => $this->date->toArray()
        ];

        return parent::toArray($values);
    }
}