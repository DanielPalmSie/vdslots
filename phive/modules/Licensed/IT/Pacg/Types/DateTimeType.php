<?php
namespace IT\Pacg\Types;

use IT\Abstractions\AbstractEntity;

/**
 * Structure for the representation of the date and time
 * Class DateTimeType
 */
class DateTimeType extends AbstractEntity
{
    public $date;
    public $time;

    protected $fillable = [
        'date',
        'time',
    ];

    protected $rules = [
        'date' => 'required',
        'time' => 'required',
    ];

    /**
     * @param array $property_values
     * @return AbstractEntity
     * @throws \Rakit\Validation\RuleQuashException
     */
    public function fill(array $property_values): AbstractEntity
    {
        parent::fill($property_values);

        if(is_array($this->date)) {
            $this->date = (new DateType())->fill($this->date);;
        }
        if(is_array($this->time)) {
            $this->time = (new TimeType())->fill($this->time);;
        }

        return $this;
    }

    /**
     * @param array $array
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