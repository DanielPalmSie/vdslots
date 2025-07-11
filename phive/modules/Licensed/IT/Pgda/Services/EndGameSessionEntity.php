<?php
namespace IT\Pgda\Services;

use IT\Abstractions\AbstractEntity;
use IT\Pgda\Type\DateTimeType;

/**
 * Session end message (500)
 * Class SessionEndMessageEntity
 * @package IT\Pgda\Entity
 */
class EndGameSessionEntity extends PgdaEntity
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
     * @var string
     */
    public $central_system_session_id;

    /**
     * @var DateTimeType
     */
    public $session_end_date;

    /**
     * @var string
     */
    protected $format = "A16n6";

    /**
     * @var array
     */
    protected $fillable = [
        'game_code',
        'game_type',
        'central_system_session_id',
        'session_end_date',
    ];


    /**
     * @param array $propertyValues
     * @return AbstractEntity
     * @throws \Rakit\Validation\RuleQuashException
     */
    public function fill(array $propertyValues): AbstractEntity
    {
        parent::fill($propertyValues);

        if (is_array($this->session_end_date)) {
            $this->session_end_date = (new DateTimeType())->fill($this->session_end_date);
        }
        return $this;
    }

    /**
     * @param array $array
     * @return array
     */
    public function toArray(array $array = []): array
    {
        return parent::toArray(
            array_merge(
                [$this->central_system_session_id],
                $this->session_end_date->toArray()
            )
        );
    }
}