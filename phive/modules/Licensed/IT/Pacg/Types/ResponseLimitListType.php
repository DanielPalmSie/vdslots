<?php
namespace IT\Pacg\Types;

use IT\Abstractions\AbstractEntity;

/**
 * Class ResponseLimitListType
 * @package IT\Pacg\Types
 */
class ResponseLimitListType extends AbstractEntity
{
    protected $fillable = [
        'limite',
    ];

    /**
     * @param array $propertyValues
     * @return AbstractEntity
     * @throws \Rakit\Validation\RuleQuashException
     */
    public function fill(array $propertyValues): AbstractEntity
    {
        parent::fill($propertyValues);
        $this->setLimitList();

        return $this;
    }

    /**
     * @throws \Exception
     */
    private function setLimitList()
    {
        $limits_data = $this->limite;
        $this->limite = [];
        if(is_array($limits_data)) {
            foreach ($limits_data as $key => $limit_data) {
                $this->limite[$key] = (new ResponseLimitType())->fill($limit_data);
            }
        }

    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getLimitList(): array
    {
        $result_limit_list = [];
        foreach ($this->limite as $limit) {
            if (! ($limit instanceof ResponseLimitType)) {
                throw new \Exception('Limit item isn\'t a LimitType object');
            }
            $result_limit_list[] = $limit->toArray();
        }
        return $result_limit_list;
    }

    /**
     * @return int
     */
    public function getNumberOfLimits(): int
    {
        return count($this->limite);
    }

    /**
     * @inheritDoc
     */
    public function toArray(array $array = []): array
    {
        return $this->getLimitList();
    }
}