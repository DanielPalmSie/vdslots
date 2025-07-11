<?php
namespace IT\Pgda\Services;

use IT\Abstractions\AbstractEntity;
use IT\Pgda\Type\GameStageListType;

/**
 * Gambling execution communication message (580)
 * Class GameExecutionCommunicationEntity
 * @package IT\Pgda\Services
 */
class GameExecutionCommunicationEntity extends PgdaEntity
{
    /**
     * @var string
     */
    public $session_id;

    /**
     * @var int
     */
    public $initial_progressive_number;

    /**
     * @var int
     */
    public $last_progressive_number;

    /**
     * @var string
     */
    public $stage_date;

    /**
     * @var int
     */
    public $flag_closing_day;

    /**
     * @var GameStageListType
     */
    public $game_stages;

    /**
     * @var string
     */
    public $format = 'A16N3A8C';

    /**
     * @var array
     */
    protected $fillable = [
        'game_code',
        'game_type',
        'session_id',
        'initial_progressive_number',
        'last_progressive_number',
        'stage_date',
        'flag_closing_day',
        'game_stages',
    ];

    /**
     * @param array $propertyValues
     * @return AbstractEntity
     * @throws \Rakit\Validation\RuleQuashException
     */
    public function fill(array $propertyValues): PgdaEntity
    {
        parent::fill($propertyValues);
        if (is_array($this->game_stages)) {
            $this->game_stages = (new GameStageListType())->fill(['game_stages' => $this->game_stages]);
            $this->format .= $this->game_stages->getFormat();
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
        $game_stage_list = [];
        $number_game_stages = 0;
        if (!empty($this->game_stages)) {
            $game_stage_list = $this->game_stages->toArray();
            $number_game_stages = $this->game_stages->getGameStageNumber();
        }
        return parent::toArray(
            array_merge(
                [
                    $this->session_id,
                    $number_game_stages,
                    $this->initial_progressive_number,
                    $this->last_progressive_number,
                    $this->stage_date,
                    $this->flag_closing_day,
                ],
                $game_stage_list
            )
        );
    }
}