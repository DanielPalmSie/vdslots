<?php

namespace IT\Pgda\Services;

use IT\Abstractions\AbstractEntity;
use IT\Pgda\Type\DateType;

/**
 * Session end date update message (810)
 * Class SessionEndDateUpdateMessageEntity
 * @package IT\Pgda\Entity
 */
class SessionEndDateUpdateRequestEntity extends PgdaEntity
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
     * @var DateType
     */
    public $end_date_session;

    /**
     * @var string
     */
    protected $format = "A16n3";

    /**
     * @var array
     */
    protected $fillable = [
        'game_code',
        'game_type',
        'central_system_session_id',
        'end_date_session',
    ];

    public function fill(array $propertyValues): AbstractEntity
    {
        parent::fill($propertyValues);

        if (is_array($this->end_date_session)) {
            $this->end_date_session = (new DateType())->fill($this->end_date_session);
        }

        return $this;
    }

    /**
     * @param array $array
     * @return array
     * @throws \Rakit\Validation\RuleQuashException
     */
    public function toArray(array $array = []): array
    {
        return parent::toArray(
            array_merge(
                [$this->central_system_session_id],
                $this->end_date_session->toArray(),
            )
        );
    }
}