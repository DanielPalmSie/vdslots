<?php
namespace IT\Pgda\Services;

use IT\Abstractions\AbstractEntity;
use IT\Pgda\Type\DateType;

/**
 * Accounting data request message (800)
 * Class RequestFinancialAccountingEntity
 * @package IT\Pgda\Services
 */
class RequestFinancialAccountingEntity extends PgdaEntity
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
    public $period_start_date;

    /**
     * @var DateType
     */
    public $period_end_date;

    /**
     * @var string
     */
    protected $format = "n6";

    /**
     * @var array
     */
    protected $fillable = [
        'game_code',
        'game_type',
        'period_start_date',
        'period_end_date'
    ];

    /**
     * @param array $propertyValues
     * @return AbstractEntity
     * @throws \Rakit\Validation\RuleQuashException
     */
    public function fill(array $propertyValues): AbstractEntity
    {
        parent::fill($propertyValues);

        if (is_array($this->period_start_date)) {
            $this->period_start_date = (new DateType())->fill($this->period_start_date);
        }

        if (is_array($this->period_end_date)) {
            $this->period_end_date = (new DateType())->fill($this->period_end_date);
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function toArray(array $array = []): array
    {
        return parent::toArray(
            array_merge(
                $this->period_start_date->toArray(),
                $this->period_end_date->toArray()
            )
        );
    }
}