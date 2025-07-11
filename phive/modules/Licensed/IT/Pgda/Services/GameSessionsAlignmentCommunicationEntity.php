<?php
namespace IT\Pgda\Services;

use IT\Abstractions\AbstractEntity;
use IT\Pgda\Type\RoundUpListType;

/**
 * Gambling session alignment message (590)
 * Class SessionAlignmentMessageEntity
 * @package IT\Pgda\Entity
 */
class GameSessionsAlignmentCommunicationEntity extends PgdaEntity
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
     * @var string
     */
    public $reference_date;

    /**
     * @var int
     */
    public $total_number_stages_played;

    /**
     * @var int
     */
    public $number_stages_completed;

    /**
     * @var RoundUpListType
     */
    public $round_up_list;

    /**
     * @var string
     */
    protected $format = "A16A8N3";

    /**
     * @var array
     */
    protected $fillable = [
        'game_code',
        'game_type',
        'central_system_session_id',
        'reference_date',
        'total_number_stages_played',
        'number_stages_completed',
        'round_up_list'
    ];

    /**
     * @param array $propertyValues
     * @return AbstractEntity
     * @throws \Rakit\Validation\RuleQuashException
     */
    public function fill(array $propertyValues): PgdaEntity
    {
        parent::fill($propertyValues);

        if (is_array($this->round_up_list)) {
            $this->round_up_list = (new RoundUpListType())->fill(['round_up' => $this->round_up_list]);
            $this->format .= $this->round_up_list->getFormat();
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
        return parent::toArray(
            array_merge(
                [
                    $this->central_system_session_id,
                    $this->reference_date,
                    $this->total_number_stages_played,
                    $this->number_stages_completed,
                    $this->round_up_list->getNumberRoundUp(),
                ],
                $this->round_up_list->toArray()
            )
        );
    }
}