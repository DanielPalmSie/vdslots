<?php
namespace IT\Pgda\Services;

use IT\Abstractions\AbstractEntity;
use IT\Pgda\Type\DateType;

/**
 * Reported anomalies request message (560)
 *
 * Class ReportedAnomaliesEntity
 * @package IT\Pgda\Services
 */
class ReportedAnomaliesEntity extends PgdaEntity
{
    /**
     * @var int
     */
    public $game_code;

    /**
     * @var int
     */
    public $game_type;
    /**
     * @var DateType
     */
    public $date_session_opened;

    /**
     * @var string
     */
    protected $format = "n3";

    /**
     * @var array
     */
    protected $fillable = [
        'game_code',
        'game_type',
        'date_session_opened'
    ];

    /**
     * @param array $propertyValues
     * @return AbstractEntity
     * @throws \Rakit\Validation\RuleQuashException
     */
    public function fill(array $propertyValues): AbstractEntity
    {
        parent::fill($propertyValues);

        if (is_array($this->date_session_opened)) {
            $this->date_session_opened = (new DateType())->fill($this->date_session_opened);
        }

        return $this;
    }


    /**
     * @inheritDoc
     */
    public function toArray(array $array = []): array
    {
        return parent::toArray($this->date_session_opened->toArray());
    }
}