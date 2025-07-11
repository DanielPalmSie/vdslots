<?php
namespace IT\Pacg\Types;

use IT\Abstractions\AbstractEntity;

/**
 * Structure for the representation of the date and time
 * Class ResponseDateTimeType
 */
class ResponseDateTimeType extends AbstractEntity
{
    protected $fillable = [
        'data',
        'ora',
    ];

    /**
     * @param array $property_values
     * @return AbstractEntity
     * @throws \Rakit\Validation\RuleQuashException
     */
    public function fill(array $property_values): AbstractEntity
    {
        parent::fill($property_values);

        if(is_array($this->data)) {
            $this->data = (new ResponseDateType())->fill($this->data);
        }
        if(is_array($this->ora)) {
            $this->ora = (new ResponseTimeType())->fill($this->ora);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getDateTime()
    {
        return "{$this->data->getDate()} {$this->ora->getTime()}";
    }

    /**
     * @return array
     */
    public function toArray(array $array = []): array
    {
        return [
            "data" => $this->date->toArray(),
            "ora"  => $this->time->toArray()
        ];
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return
            "Date\n{$this->date->toString()}\n".
            "Time\n{$this->time->toString()}";
    }

}