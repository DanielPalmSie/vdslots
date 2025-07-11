<?php
namespace IT\Pacg\Types;

use IT\Abstractions\AbstractEntity;

/**
 * Class BonusDetailListType
 * @package IT\Pacg\Types
 */
class BonusDetailListType extends AbstractEntity
{
    public $bonus_detail;

    protected $fillable = [
        'bonus_detail',
    ];

    /**
     * @param array $property_values
     * @return AbstractEntity
     * @throws \Rakit\Validation\RuleQuashException
     */
    public function fill(array $property_values): AbstractEntity
    {
        parent::fill($property_values);
        $this->setBonusDetailList();

        return $this;
    }

    /**
     * @throws \Exception
     */
    private function setBonusDetailList()
    {
        $bonus_detail_data = $this->bonus_detail;
        $this->bonus_detail = [];
        foreach ($bonus_detail_data as $key => $bonus_detail) {
            $this->bonus_detail[$key] = (new BonusDetailType())->fill($bonus_detail);

            // Collect the validation errors in the list object
            if(!empty($this->bonus_detail[$key]->errors)) {
                $this->errors = array_merge($this->errors, $this->bonus_detail[$key]->errors);
            }
        }
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getBonusDetailsList(): array
    {
        $result_bonus_details_list = [];
        foreach ($this->bonus_detail as $bonus) {
            if (! ($bonus instanceof BonusDetailType)) {
                throw new \Exception('Bonus Details item isn\'t a BonusDetailType object');
            }
            $result_bonus_details_list[] = $bonus->toArray();
        }

        return $result_bonus_details_list;
    }

    /**
     * @return int
     */
    public function getNumberOfBonuses(): int
    {
        return count($this->bonus_detail);
    }

    /**
     * @param array $array
     * @return array
     * @throws \Exception
     */
    public function toArray(array $array = []): array
    {
        return $this->getBonusDetailsList();
    }
}