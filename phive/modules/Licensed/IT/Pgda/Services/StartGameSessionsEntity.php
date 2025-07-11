<?php

namespace IT\Pgda\Services;

use IT\Abstractions\AbstractEntity;
use IT\Pgda\Type\AttributesSessionListType;
use IT\Pgda\Type\DateTimeType;
use IT\Pgda\Type\DateType;

/**
 * Session start message (400)
 * Class StartGameSessionsEntity
 * @package IT\Pgda\Entity
 */
class StartGameSessionsEntity extends PgdaEntity
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
    public $license_session_id;

    /**
     * @var DateTimeType
     */
    public $start_date_session;

    /**
     * @var DateType
     */
    public $end_date_session;

    /**
     * @var AttributesSessionListType
     */
    public $attributes_session_list;

    /**
     * @var string
     */
    protected $format = "A16n9N";

    /**
     * @var array
     */
    protected $fillable = [
        'game_code',
        'game_type',
        'license_session_id',
        'start_date_session',
        'end_date_session',
        'attributes_session_list',
    ];

    /**
     * @param array $propertyValues
     * @return AbstractEntity
     * @throws \Rakit\Validation\RuleQuashException
     */
    public function fill(array $propertyValues): AbstractEntity
    {
        parent::fill($propertyValues);

        if (is_array($this->start_date_session)) {
            $this->start_date_session = (new DateTimeType())->fill($this->start_date_session);
        }
        if (is_array($this->end_date_session)) {
            $this->end_date_session = (new DateType())->fill($this->end_date_session);
        }
        if (is_array($this->attributes_session_list)) {
            $this->attributes_session_list = (new AttributesSessionListType())->fill(['attributes_session' => $this->attributes_session_list]);
            $this->format .= $this->attributes_session_list->getFormat();
        }

        return $this;
    }

    /**
     * @param array $array
     * @return array
     * @throws \Exception
     */
    public function toArray(array $array = []): array
    {
        $attributes_session_list = [];
        $number_attributes = 0;
        if (!empty($this->attributes_session_list)) {
            $attributes_session_list = $this->attributes_session_list->toArray();
            $number_attributes = $this->attributes_session_list->getNumberAttributes();
        }
        return parent::toArray(array_merge(
            [$this->license_session_id],
            $this->start_date_session->toArray(),
            $this->end_date_session->toArray(),
            [$number_attributes],
            $attributes_session_list
        ));
    }
}