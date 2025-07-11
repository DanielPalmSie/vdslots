<?php
namespace IT\Pacg\Types;

use IT\Abstractions\AbstractEntity;

/**
 * Class TimeType
 * @package IT\Pacg\Types
 */
class TimeType extends AbstractEntity
{
    public $hours;
    public $minutes;
    public $seconds;

    protected $fillable = [
        'hours',
        'minutes',
        'seconds',
    ];

    protected $rules = [
        'hours'   => 'required|date:H',
        'minutes' => 'required|date:i',
        'seconds' => 'required|date:s',
    ];

    /**
     * @param array $array
     * @return array
     */
    public function toArray(array $array = []): array
    {
        return [
            "ore"     => $this->hours,
            "minuti"  => $this->minutes,
            "secondi" => $this->seconds,
        ];
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return
            "Hours: {$this->hours}\n" .
            "Minutes: {$this->minutes}\n" .
            "Seconds: {$this->seconds}";
    }
}