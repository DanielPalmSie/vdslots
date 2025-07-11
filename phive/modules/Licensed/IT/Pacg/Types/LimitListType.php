<?php
namespace IT\Pacg\Types;

use IT\Abstractions\AbstractEntity;

/**
 * Class LimitListType
 * @package IT\Pacg\Types
 */
class LimitListType extends AbstractEntity
{
    public $limits;

    protected $fillable = [
        'limits',
    ];

    /**
     * @param array $property_values
     * @return AbstractEntity
     * @throws \Rakit\Validation\RuleQuashException
     */
    public function fill(array $property_values): AbstractEntity
    {
        parent::fill($property_values);
        $this->setLimitList();

        return $this;
    }

    /**
     * @throws \Exception
     */
    private function setLimitList()
    {
        $limits_data = $this->limits;
        $this->limits = [];
        if(is_array($limits_data)) {
            foreach ($limits_data as $key => $limit_data) {
                $this->limits[$key] = (new LimitType())->fill($limit_data);

                // Collect the validation errors in the list object
                if(!empty($this->limits[$key]->errors)) {
                    $this->errors = array_merge($this->errors, $this->limits[$key]->errors);
                }
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
        foreach ($this->limits as $limit) {
            if (! ($limit instanceof LimitType)) {
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
        return count($this->limits);
    }

    /**
     * @param array $array
     * @return array
     * @throws \Exception
     */
    public function toArray(array $array = []): array
    {
        return $this->getLimitList();
    }
}