<?php

namespace IT\Pgda\Type;

use IT\Abstractions\AbstractEntity;
use IT\Pgda\Services\PgdaEntity;
use Rakit\Validation\RuleQuashException;

class GameStageListType extends PgdaEntity
{
    /**
     * @var AttributesSessionType[]
     */
    protected $game_stages = [];

    /**
     * @var array
     */
    protected $fillable = [
        'game_stages',
    ];

    /**
     * @param array $property_values
     * @return AbstractEntity
     * @throws RuleQuashException
     */
    public function fill(array $property_values): AbstractEntity
    {
        parent::fill($property_values);
        $this->setGameStages();
        return $this;
    }

    /**
     * @throws RuleQuashException
     */
    protected function setGameStages()
    {
        $game_stages = $this->game_stages;
        $this->game_stages = [];
        foreach ($game_stages as $key => $game_stage) {
            $this->game_stages[$key] = (new GameStageType())->fill($game_stage);
            if (!empty($this->game_stages[$key]->errors)) {
                $this->errors = array_merge($this->errors, $this->game_stages[$key]->errors);
            }
        }
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getGameStages(): array
    {
        $result_game_stage_list = [];
        foreach ($this->game_stages as $game_stage) {
            if (!($game_stage instanceof GameStageType)) {
                throw new \Exception('Game stage item isn\'t a GameStagesType object');
            }
            foreach ($game_stage->toArray() as $name => $field) {
                $result_game_stage_list[] = $field;
            }
        }

        return $result_game_stage_list;
    }

    /**
     * @return int
     */
    public function getGameStageNumber(): int
    {
        return count($this->game_stages);
    }

    /**
     * @return string
     */
    public function getFormat(): string
    {
        $format = "";
        $format_string = end($this->game_stages)->getFormat();
        for ($i = 0; $i < $this->getGameStageNumber(); $i++) {
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
        return $this->getGameStages();
    }
}
