<?php
namespace IT\Pgda\Type;

use IT\Abstractions\AbstractEntity;
use IT\Pgda\Services\PgdaEntity;
use Rakit\Validation\RuleQuashException;

/**
 * Class PlayerListType
 * @package IT\Pgda\Type
 */
class PlayerListType extends PgdaEntity
{
    /**
     * @var PlayerType[]
     */
    protected $players = [];

    /**
     * @var array
     */
    protected $fillable = [
        'players',
    ];

    /**
     * @param array $property_values
     * @return AbstractEntity
     * @throws RuleQuashException
     */
    public function fill(array $property_values): AbstractEntity
    {
        parent::fill($property_values);
        $this->setPlayers();
        return $this;
    }

    /**
     * @throws RuleQuashException
     */
    protected function setPlayers()
    {
        $players = $this->players;
        $this->players = [];
        foreach ($players as $key => $player) {
            $this->players[$key] = (new PlayerType())->fill($player);
            if (!empty($this->players[$key]->errors)) {
                $this->errors = array_merge($this->errors, $this->players[$key]->errors);
            }
        }
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getPlayers(): array
    {
        $result_players = [];
        foreach ($this->players as $player) {
            if (!($player instanceof PlayerType)) {
                throw new \Exception('Player item isn\'t a PlayerType object');
            }
            foreach ($player->toArray() as $name => $field) {
                $result_players[] = $field;
            }
        }

        return $result_players;
    }

    /**
     * @return int
     */
    public function getNumberPlayers(): int
    {
        return count($this->players);
    }

    /**
     * @return string
     */
    public function getFormat(): string
    {
        $format = "";
        $format_string = end($this->players)->getFormat();
        for ($i = 0; $i < $this->getNumberPlayers(); $i++) {
            $format .= $format_string;
        }

        return $format;
    }

    /**
     * @param array $array
     * @return array
     * @throws \Exception
     */
    public function toArray(array $array = []): array
    {
        return $this->getPlayers();
    }
}