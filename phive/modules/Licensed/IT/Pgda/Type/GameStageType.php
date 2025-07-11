<?php
namespace IT\Pgda\Type;

use IT\Abstractions\AbstractEntity;
use IT\Pgda\Services\PgdaEntity;

/**
 * Class GameStageType
 * @package IT\Pgda\Type
 */
class GameStageType extends PgdaEntity
{
    /**
     * @var int
     */
    protected $total_taxable_amount;

    /**
     * @var int
     */
    protected $stage_progressive_number;

    /**
     * @var string
     */
    protected $datetime;

    /**
     * @var PlayerListType
     */
    public $players;

    /**
     * @var string
     */
    protected $format = 'NJNA14';

    /**
     * @var array
     */
    protected $fillable = [
        'total_taxable_amount',
        'stage_progressive_number',
        'datetime',
        'players',
    ];

    /**
     * @param array $propertyValues
     * @return AbstractEntity
     * @throws \Rakit\Validation\RuleQuashException
     */
    public function fill(array $propertyValues): PgdaEntity
    {
        parent::fill($propertyValues);

        if (is_array($this->players)) {
            $this->players = (new PlayerListType())->fill(['players' => $this->players]);
            $this->format .= $this->players->getFormat();
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
        $player_list = [];
        $number_players = 0;
        if (!empty($this->players)) {
            $player_list = $this->players->toArray();
            $number_players = $this->players->getNumberPlayers();
        }
        return array_merge(
            [
                $number_players,
                $this->total_taxable_amount,
                $this->stage_progressive_number,
                $this->datetime,
            ],
            $player_list
        );
    }
}