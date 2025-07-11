<?php
namespace IT\Pacg\Types;

use IT\Abstractions\AbstractEntity;

/**
 * Class DateType
 * @package IT\Pacg\Types
 */
class DateType extends AbstractEntity
{
    public $day;
    public $month;
    public $year;

    protected $fillable = [
        'day',
        'month',
        'year',
    ];

    protected $rules = [
        'day'   => 'required|date:d',
        'month' => 'required|date:m',
        'year'  => 'required|date:Y',
    ];

    /**
     * @param array $array
     * @return array
     */
    public function toArray(array $array = []): array
    {
        return [
            "giorno" => $this->day,
            "mese"   => $this->month,
            "anno"   => $this->year,
        ];
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return
            "Day: {$this->day}\n" .
            "Month: {$this->month}\n" .
            "Year: {$this->year}";
    }

}