<?php
namespace IT\Pacg\Types;

use IT\Abstractions\AbstractEntity;

/**
 * Class ResponseTimeType
 * @package IT\Pacg\Types
 */
class ResponseTimeType extends AbstractEntity
{
    /**
     * @var array
     */
    protected $fillable = [
        'ore',
        'minuti',
        'secondi',
    ];

    /**
     * @return string
     */
    public function getTime()
    {
        return "{$this->ore}:{$this->minuti}:{$this->secondi}";
    }

    /**
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

}