<?php
namespace IT\Pgda\Type;

use IT\Abstractions\AbstractEntity;
use IT\Pgda\Services\PgdaEntity;

/**
 * Class DateTimeType
 * @package IT\Pgda\Type
 */
class DateTimeType extends PgdaEntity
{
    /**
     * @var DateType
     */
    protected $date;

    /**
     * @var TimeType
     */
    protected $time;

    /**
     * @var array
     */
    protected $fillable = [
        'date',
        'time',
    ];

    public function fill(array $propertyValues): AbstractEntity
    {
        parent::fill($propertyValues);

        if (is_array($this->date)) {
            $this->date = (new DateType())->fill($this->date);
        }
        if (is_array($this->time)) {
            $this->time = (new TimeType())->fill($this->time);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getFormat(): string
    {
        return  $this->date->getFormat() . $this->time->getFormat();
    }

    /**
     * @param array $array
     * @return array
     */
    public function toArray(array $array = []): array
    {
        return array_merge($this->date->toArray(), $this->time->toArray());
    }
}