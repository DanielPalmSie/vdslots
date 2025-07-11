<?php

namespace IT\Pgda\Type;

use IT\Abstractions\AbstractEntity;
use IT\Pgda\Client\PgdaClient;
use IT\Pgda\Services\PgdaEntity;
use Rakit\Validation\RuleQuashException;

/**
 * Class RoundUpListType
 * @package IT\Pgda\Type
 */
class RoundUpListType extends PgdaEntity
{
    /**
     * @var RoundUpType[]
     */
    protected $round_up = [];

    /**
     * @var array
     */
    protected $fillable = [
        'round_up',
    ];

    public function fill(array $property_values): AbstractEntity
    {
        parent::fill($property_values);
        $this->setRoundUp();
        return $this;
    }

    /**
     * @throws RuleQuashException
     */
    protected function setRoundUp()
    {
        $rounds_up = $this->round_up;
        $this->round_up = [];
        foreach ($rounds_up as $key => $round_up) {
            $this->round_up[$key] = (new RoundUpType())->fill($round_up);
            if (!empty($this->round_up[$key]->errors)) {
                $this->errors = array_merge($this->errors, $this->round_up[$key]->errors);
            }
        }
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getRoundUp(): array
    {
        $result_round_up_list = [];
        foreach ($this->round_up as $round_up) {
            if (!($round_up instanceof RoundUpType)) {
                throw new \Exception('Round Up item isn\'t a RoundUpType object');
            }
            foreach ($round_up->toArray() as $name => $field) {
                $result_round_up_list[] = $field;
            }
        }

        return $result_round_up_list;
    }

    /**
     * @return int
     */
    public function getNumberRoundUp(): int
    {
        return count($this->round_up);
    }

    /**
     * @return string
     */
    public function getFormat(): string
    {
        $format = "";
        $format_string = end($this->round_up)->getFormat();
        for ($i = 0; $i < $this->getNumberRoundUp(); $i++) {
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
        return $this->getRoundUp();
    }
}