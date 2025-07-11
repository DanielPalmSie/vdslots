<?php
namespace IT\Pgda\Type;

use IT\Pgda\Services\PgdaEntity;

/**
 * Class TimeType
 * @package IT\Pgda\Type
 */
class TimeType extends PgdaEntity
{
    /**
     * @var int
     */
    protected $hour;

    /**
     * @var int
     */
    protected $minutes;

    /**
     * @var int
     */
    protected $seconds;

    /**
     * @var string
     */
    protected $format = 'n3';

    /**
     * @var array
     */
    protected $fillable = [
        'hour',
        'minutes',
        'seconds',
    ];

    /**
     * @param array $array
     * @return array
     */
    public function toArray(array $array = []): array
    {
        return [
            $this->hour,
            $this->minutes,
            $this->seconds,
        ];
    }
}