<?php
namespace IT\Pgda\Type;

use IT\Abstractions\AbstractEntity;
use IT\Pgda\Client\PgdaClient;
use IT\Pgda\Services\PgdaEntity;

/**
 * Class DateType
 * @package IT\Pgda\Type
 */
class DateType extends PgdaEntity
{
    /**
     * @var int
     */
    protected $day;

    /**
     * @var int
     */
    protected $month;

    /**
     * @var int
     */
    protected $year;

    /**
     * @var string
     */
    protected $format = 'n3';

    /**
     * @var array
     */
    protected $fillable = [
        'day',
        'month',
        'year',
    ];

    /**
     * @param array $array
     * @return array
     */
    public function toArray(array $array = []): array
    {
        return [
            $this->day,
            $this->month,
            $this->year,
        ];
    }
}